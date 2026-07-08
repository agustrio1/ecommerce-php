<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence;

use App\Modules\Cart\Domain\Entities\CartItem;
use PDO;

class MysqlCartRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Ambil atau buat cart.
     *
     * PENTING (fix bug cart guest "hilang" setelah login): untuk user yang
     * SUDAH LOGIN, cart dicari berdasarkan user_id — BUKAN session_id.
     * Sebelumnya method ini selalu mencari berdasarkan session_id, padahal
     * session_id() SELALU BERUBAH setiap kali user login (karena
     * Session::loginUserId() memanggil session_regenerate_id() untuk
     * mencegah session fixation attack). Akibatnya: cart yang baru saja
     * digabungkan dari guest cart (lewat mergeGuestCartIntoUser() di
     * AuthController) langsung "kehilangan jejak" pada request berikutnya,
     * karena request itu punya session_id BARU yang tidak match dengan
     * session_id LAMA yang tersimpan di baris cart — sehingga method ini
     * mengira belum ada cart sama sekali dan diam-diam membuat cart BARU
     * yang kosong untuk user tersebut.
     *
     * Untuk GUEST (belum login), pencarian tetap berdasarkan session_id
     * seperti sebelumnya, karena itu satu-satunya identitas yang tersedia.
     */
    public function findOrCreateCart(string $sessionId, ?int $userId = null): int
    {
        if ($userId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();

            if ($row) {
                // Sinkronkan session_id cart ini ke session yang sedang
                // aktif, supaya tetap konsisten kalau ada bagian lain kode
                // yang masih bergantung pada session_id (jaga-jaga).
                $update = $this->pdo->prepare('UPDATE carts SET session_id = :session_id WHERE id = :id');
                $update->execute(['session_id' => $sessionId, 'id' => $row['id']]);

                return (int) $row['id'];
            }

            // User login tapi belum pernah punya cart sama sekali (baru
            // daftar, atau memang belum pernah checkout/nambah cart
            // sebelumnya) — buat baru terikat langsung ke user_id.
            $stmt = $this->pdo->prepare(
                'INSERT INTO carts (session_id, user_id, created_at, updated_at)
                 VALUES (:session_id, :user_id, NOW(), NOW())'
            );
            $stmt->execute(['session_id' => $sessionId, 'user_id' => $userId]);

            return (int) $this->pdo->lastInsertId();
        }

        // Guest (belum login): cari berdasarkan session_id, dan pastikan
        // hanya mengambil cart yang memang belum terikat ke user manapun
        // (user_id IS NULL) — supaya tidak ada kemungkinan tanpa sengaja
        // "menyerobot" cart milik user lain yang kebetulan session_id-nya
        // sama persis (praktis mustahil, tapi eksplisit lebih aman).
        $stmt = $this->pdo->prepare(
            'SELECT id FROM carts WHERE session_id = :session_id AND user_id IS NULL LIMIT 1'
        );
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO carts (session_id, user_id, created_at, updated_at)
             VALUES (:session_id, NULL, NOW(), NOW())'
        );
        $stmt->execute(['session_id' => $sessionId]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Ambil semua item cart beserta data produk & variant (JOIN).
     * @return CartItem[]
     */
    public function getItems(int $cartId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                ci.id,
                ci.cart_id,
                ci.product_id,
                ci.variant_id,
                ci.price,
                ci.quantity,
                p.name AS product_name,
                p.sku AS product_sku,
                p.weight,
                p.length,
                p.width,
                p.height,
                COALESCE(
                    (SELECT GROUP_CONCAT(av.value ORDER BY a.name SEPARATOR " / ")
                     FROM variant_attribute_values vav
                     JOIN attribute_values av ON av.id = vav.attribute_value_id
                     JOIN attributes a ON a.id = av.attribute_id
                     WHERE vav.variant_id = ci.variant_id),
                    "Default"
                ) AS variant_label,
                pv.sku AS product_sku,
                (SELECT pi.path FROM product_images pi
                 WHERE pi.product_id = ci.product_id AND pi.is_primary = 1
                 LIMIT 1) AS product_image
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             JOIN product_variants pv ON pv.id = ci.variant_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.created_at ASC'
        );
        $stmt->execute(['cart_id' => $cartId]);

        return array_map(fn ($row) => CartItem::fromArray($row), $stmt->fetchAll());
    }

    /**
     * Tambah item ke cart. Kalau variant sudah ada, tambah quantity DAN
     * update harga ke harga terbaru (misalnya flash sale baru mulai/berakhir
     * di antara dua kali user menambahkan produk yang sama).
     */
    public function addItem(int $cartId, int $productId, int $variantId, int $quantity, float $price): void
    {
        $existing = $this->findItem($cartId, $variantId);

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $this->updateQuantityAndPrice($cartId, $variantId, $newQty, $price);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, created_at, updated_at)
                 VALUES (:cart_id, :product_id, :variant_id, :quantity, :price, NOW(), NOW())'
            );
            $stmt->execute([
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'price'      => $price,
            ]);
        }
    }

    /**
     * Update quantity SAJA — dipakai saat user klik tombol +/- di halaman
     * cart. Harga tidak sengaja diubah di sini; sinkronisasi harga flash
     * sale ditangani terpisah lewat syncPrice() (dipanggil dari
     * CartService::getItems()).
     */
    public function updateQuantity(int $cartId, int $variantId, int $quantity): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cart_items SET quantity = :quantity, updated_at = NOW()
             WHERE cart_id = :cart_id AND variant_id = :variant_id'
        );
        $stmt->execute(['quantity' => $quantity, 'cart_id' => $cartId, 'variant_id' => $variantId]);
    }

    /**
     * Update quantity DAN price sekaligus — dipakai khusus dari addItem()
     * saat variant yang sama ditambahkan lagi.
     */
    private function updateQuantityAndPrice(int $cartId, int $variantId, int $quantity, float $price): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cart_items SET quantity = :quantity, price = :price, updated_at = NOW()
             WHERE cart_id = :cart_id AND variant_id = :variant_id'
        );
        $stmt->execute([
            'quantity'   => $quantity,
            'price'      => $price,
            'cart_id'    => $cartId,
            'variant_id' => $variantId,
        ]);
    }

    /**
     * Update HARGA SAJA (tanpa ubah quantity) — dipakai untuk sinkronisasi
     * harga flash sale tiap kali cart dibuka/di-load, lewat
     * CartService::getItems(). Ini yang bikin harga di cart "ikut" status
     * flash sale terkini, bukan cuma harga yang dikunci saat pertama
     * ditambahkan.
     */
    public function syncPrice(int $cartId, int $variantId, float $price): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cart_items SET price = :price, updated_at = NOW()
             WHERE cart_id = :cart_id AND variant_id = :variant_id'
        );
        $stmt->execute(['price' => $price, 'cart_id' => $cartId, 'variant_id' => $variantId]);
    }

    public function removeItem(int $cartId, int $variantId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM cart_items WHERE cart_id = :cart_id AND variant_id = :variant_id'
        );
        $stmt->execute(['cart_id' => $cartId, 'variant_id' => $variantId]);
    }

    public function clearCart(int $cartId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute(['cart_id' => $cartId]);
    }

    public function countItems(int $cartId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute(['cart_id' => $cartId]);

        return (int) $stmt->fetchColumn();
    }

    private function findItem(int $cartId, int $variantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cart_items WHERE cart_id = :cart_id AND variant_id = :variant_id LIMIT 1'
        );
        $stmt->execute(['cart_id' => $cartId, 'variant_id' => $variantId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}