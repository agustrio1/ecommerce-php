<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Services;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Session;
use App\Modules\Cart\Domain\Entities\CartItem;
use App\Modules\Cart\Infrastructure\Persistence\MysqlCartRepository;
use App\Modules\Product\Infrastructure\Persistence\MysqlProductVariantRepository;
use PDO;

class CartService
{
    private MysqlCartRepository $cartRepo;
    private MysqlProductVariantRepository $variantRepo;
    private PDO $pdo;

    public function __construct()
    {
        $this->cartRepo    = new MysqlCartRepository();
        $this->variantRepo = new MysqlProductVariantRepository();
        $this->pdo         = db();
    }

    /**
     * Ambil cart ID berdasarkan session saat ini.
     * Buat cart baru jika belum ada.
     */
    public function getCartId(): int
    {
        $sessionId = session_id();
        $userId    = Session::userId();

        return $this->cartRepo->findOrCreateCart($sessionId, $userId);
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        $cartId = $this->getCartId();
        $items  = $this->cartRepo->getItems($cartId);

        if (empty($items)) {
            return $items;
        }

        // PENTING: sinkronkan harga tiap item cart dengan status flash sale
        // TERKINI (bukan harga yang "dikunci" saat pertama kali ditambahkan).
        // Kalau ada perbedaan, harga di database langsung di-update di sini,
        // lalu kita fetch ULANG dari database supaya entity yang dikembalikan
        // ke caller (halaman cart, checkout, order) selalu konsisten dengan
        // apa yang baru saja disimpan — bukan objek lama yang di-fetch
        // sebelum sinkronisasi terjadi.
        $this->refreshFlashSalePrices($cartId, $items);

        return $this->cartRepo->getItems($cartId);
    }

    public function countItems(): int
    {
        return $this->cartRepo->countItems($this->getCartId());
    }

    public function subtotal(): float
    {
        return array_reduce(
            $this->getItems(),
            fn ($carry, CartItem $item) => $carry + $item->subtotal(),
            0.0
        );
    }

    public function totalWeight(): float
    {
        return array_reduce(
            $this->getItems(),
            fn ($carry, CartItem $item) => $carry + $item->totalWeight(),
            0.0
        );
    }

    /**
     * Ambil metadata flash sale (bukan harga) untuk keperluan TAMPILAN saja —
     * badge "Flash Sale", harga coret, sisa stok flash sale, dsb.
     * Dipanggil terpisah dari getItems() supaya CartService tetap simpel:
     * getItems() urusannya harga yang benar, method ini urusannya info
     * tambahan buat UI.
     *
     * @param CartItem[] $items
     * @return array<int, array{is_flash_sale: bool, original_price: ?float, stock_limit: ?int, sold_count: ?int}>
     *         key = variant_id
     */
    public function getFlashSaleInfoForItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();
        $info = [];

        foreach ($items as $item) {
            $fs          = $flashSaleService->getActivePriceForProduct($item->productId);
            $isFlashSale = $fs !== null && ! ($fs['is_exhausted'] ?? false);

            $info[$item->variantId] = [
                'is_flash_sale'  => $isFlashSale,
                'original_price' => $isFlashSale
                    ? $this->getCurrentNormalPrice($item->variantId, $item->productId)
                    : null,
                'stock_limit'    => $fs['stock_limit'] ?? null,
                'sold_count'     => $fs['sold_count'] ?? null,
            ];
        }

        return $info;
    }

    /**
     * Dipakai controller saat user pilih attribute (warna, ukuran, dst)
     * di halaman produk lalu klik "+ Keranjang" / "Beli Sekarang".
     *
     * Resolve dulu kombinasi attribute_value_id yang dipilih user ke
     * variant_id yang cocok lewat tabel pivot
     * `product_variant_attribute_values`, baru delegasikan ke addItem()
     * yang sudah ada (biar validasi stok, flash sale, dll tetap satu jalur).
     *
     * @param int[] $selectedAttributeValueIds contoh: [warna_id, ukuran_id]
     */
    public function addItemByAttributes(int $productId, array $selectedAttributeValueIds, int $quantity = 1): void
    {
        if (empty($selectedAttributeValueIds)) {
            throw new ValidationException(['variant' => 'Silakan pilih varian produk terlebih dahulu.']);
        }

        // Pastikan semua ID berupa integer, hindari masalah tipe data
        // dari input frontend (string/JSON) yang bisa bikin query pivot gagal match.
        $selectedAttributeValueIds = array_map('intval', $selectedAttributeValueIds);

        $variant = $this->variantRepo->findVariantByAttributeValues($productId, $selectedAttributeValueIds);

        if ($variant === null) {
            throw new ValidationException(['variant' => 'Varian produk tidak ditemukan.']);
        }

        $this->addItem($productId, (int) $variant['id'], $quantity);
    }

    /**
     * Tambah produk ke cart.
     * Validasi stok sebelum menambah.
     */
    public function addItem(int $productId, int $variantId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new ValidationException(['quantity' => 'Jumlah minimal 1.']);
        }

        $variant = $this->findVariant($variantId, $productId);

        if ($variant === null) {
            throw new ValidationException(['variant' => 'Varian produk tidak ditemukan.']);
        }

        if (! $variant['is_active']) {
            throw new ValidationException(['variant' => 'Varian produk tidak tersedia.']);
        }

        $cartId   = $this->getCartId();
        $existing = $this->getExistingQty($cartId, $variantId);
        $newTotal = $existing + $quantity;

        if ($newTotal > $variant['stock']) {
            throw new ValidationException([
                'stock' => "Stok tidak cukup. Stok tersedia: {$variant['stock']}, sudah di cart: {$existing}.",
            ]);
        }

        // Cek flash sale price — pakai harga flash sale kalau ada
        $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();
        $flashSaleInfo    = $flashSaleService->getActivePriceForProduct($productId);

        if ($flashSaleInfo && ! $flashSaleInfo['is_exhausted']) {
            $price = $flashSaleInfo['sale_price'];
        } else {
            // Fallback ke harga normal
            $price = $variant['price'] !== null ? (float) $variant['price'] : (float) $variant['product_price'];
        }

        $this->cartRepo->addItem($cartId, $productId, $variantId, $quantity, $price);
    }

    /**
     * Update jumlah item tertentu di cart.
     */
    public function updateQuantity(int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeItem($variantId);
            return;
        }

        $cartId  = $this->getCartId();
        $variant = $this->pdo->prepare('SELECT stock FROM product_variants WHERE id = :id LIMIT 1');
        $variant->execute(['id' => $variantId]);
        $row = $variant->fetch();

        if ($row && $quantity > (int) $row['stock']) {
            throw new ValidationException(['stock' => "Stok tidak cukup. Stok tersedia: {$row['stock']}."]);
        }

        $this->cartRepo->updateQuantity($cartId, $variantId, $quantity);
    }

    public function removeItem(int $variantId): void
    {
        $this->cartRepo->removeItem($this->getCartId(), $variantId);
    }

    public function clearCart(): void
    {
        $this->cartRepo->clearCart($this->getCartId());
    }

    public function isEmpty(): bool
    {
        return $this->countItems() === 0;
    }

    /**
     * Bangun array items dalam format yang dibutuhkan Biteship Rates API.
     */
    public function toBiteshipItems(): array
    {
        return array_map(fn (CartItem $item) => [
            'name'        => $item->productName,
            'description' => $item->variantLabel !== 'Default' ? $item->variantLabel : '',
            'sku'         => $item->productSku,
            'value'       => (int) $item->price,
            'quantity'    => $item->quantity,
            'weight'      => (int) max(1, $item->totalWeight()),
            'length'      => $item->length ?: 10,
            'width'       => $item->width ?: 10,
            'height'      => $item->height ?: 10,
        ], $this->getItems());
    }

    /**
     * Cek harga flash sale terkini tiap item, dan kalau beda dari harga
     * tersimpan, update langsung ke database. Ini "efek samping" yang
     * disengaja — dipanggil dari getItems() supaya harga cart selalu
     * konsisten dengan status flash sale terbaru tiap kali cart dibuka,
     * bukan cuma pas item pertama kali/kembali ditambahkan.
     *
     * @param CartItem[] $items
     */
    private function refreshFlashSalePrices(int $cartId, array $items): void
    {
        $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();

        foreach ($items as $item) {
            $fs          = $flashSaleService->getActivePriceForProduct($item->productId);
            $isFlashSale = $fs !== null && ! ($fs['is_exhausted'] ?? false);

            $currentPrice = $isFlashSale
                ? (float) $fs['sale_price']
                : $this->getCurrentNormalPrice($item->variantId, $item->productId);

            // Toleransi kecil untuk floating point, hindari update sia-sia
            // kalau harga sebenarnya sama persis.
            if (abs($currentPrice - (float) $item->price) > 0.0001) {
                $this->cartRepo->syncPrice($cartId, $item->variantId, $currentPrice);
            }
        }
    }

    /**
     * Ambil harga NORMAL (bukan flash sale) yang berlaku sekarang untuk
     * kombinasi variant+produk tertentu, langsung dari tabel produk/variant
     * (bukan dari cart_items yang bisa saja basi).
     */
    private function getCurrentNormalPrice(int $variantId, int $productId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT pv.price AS variant_price, p.price AS product_price
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.id = :variant_id AND pv.product_id = :product_id
             LIMIT 1'
        );
        $stmt->execute(['variant_id' => $variantId, 'product_id' => $productId]);
        $row = $stmt->fetch();

        if (! $row) {
            return 0.0;
        }

        return $row['variant_price'] !== null ? (float) $row['variant_price'] : (float) $row['product_price'];
    }

    private function findVariant(int $variantId, int $productId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pv.*, p.price AS product_price
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.id = :variant_id AND pv.product_id = :product_id
             LIMIT 1'
        );
        $stmt->execute(['variant_id' => $variantId, 'product_id' => $productId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function getExistingQty(int $cartId, int $variantId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT quantity FROM cart_items WHERE cart_id = :cart_id AND variant_id = :variant_id LIMIT 1'
        );
        $stmt->execute(['cart_id' => $cartId, 'variant_id' => $variantId]);
        $row = $stmt->fetch();

        return $row ? (int) $row['quantity'] : 0;
    }
}
