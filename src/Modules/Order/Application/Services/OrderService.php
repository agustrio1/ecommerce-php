<?php

declare(strict_types=1);

namespace App\Modules\Order\Application\Services;

use App\Core\Exceptions\ValidationException;
use App\Modules\Cart\Application\Services\CartService;
use App\Modules\Cart\Domain\Entities\Address;
use App\Modules\Cart\Domain\Entities\CartItem;
use App\Modules\Setting\Application\Services\SettingService;
use PDO;
use RuntimeException;

class OrderService
{
    private PDO $pdo;
    private SettingService $setting;

    public function __construct()
    {
        $this->pdo     = db();
        $this->setting = SettingService::getInstance();
    }

    /**
     * Buat order baru dari cart + alamat + kurir yang dipilih.
     * Return: order array dengan ID dan order_number.
     */
    public function createFromCart(
        CartService $cartService,
        Address $address,
        string $courierCompany,
        string $courierType,
        string $courierServiceName,
        float $shippingCost,
        ?int $userId = null,
        ?string $notes = null,
        ?string $couponCode = null,
        float $couponDiscount = 0.0
    ): array {
        $items    = $cartService->getItems();

        if (empty($items)) {
            throw new ValidationException(['cart' => 'Keranjang belanja kosong.']);
        }

        $subtotal = $cartService->subtotal();
        $discount = $couponDiscount;
        $total    = max(0, $subtotal + $shippingCost - $discount);

        $orderNumber = $this->generateOrderNumber();

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (
                    order_number, user_id, address_id, status,
                    subtotal, shipping_cost, discount, total,
                    courier_company, courier_type, courier_service_name,
                    recipient_name, recipient_phone, shipping_address,
                    shipping_district, shipping_city, shipping_province,
                    shipping_postal_code, shipping_area_id,
                    shipping_latitude, shipping_longitude,
                    coupon_code, coupon_discount, notes,
                    created_at, updated_at
                ) VALUES (
                    :order_number, :user_id, :address_id, "pending",
                    :subtotal, :shipping_cost, :discount, :total,
                    :courier_company, :courier_type, :courier_service_name,
                    :recipient_name, :recipient_phone, :shipping_address,
                    :shipping_district, :shipping_city, :shipping_province,
                    :shipping_postal_code, :shipping_area_id,
                    :shipping_latitude, :shipping_longitude,
                    :coupon_code, :coupon_discount, :notes,
                    NOW(), NOW()
                )'
            );

            $stmt->execute([
                'order_number'         => $orderNumber,
                'user_id'              => $userId,
                'address_id'           => $address->id,
                'subtotal'             => $subtotal,
                'shipping_cost'        => $shippingCost,
                'discount'             => $discount,
                'total'                => $total,
                'courier_company'      => $courierCompany,
                'courier_type'         => $courierType,
                'courier_service_name' => $courierServiceName,
                'recipient_name'       => $address->recipientName,
                'recipient_phone'      => $address->phone,
                'shipping_address'     => $address->address,
                'shipping_district'    => $address->district,
                'shipping_city'        => $address->city,
                'shipping_province'    => $address->province,
                'shipping_postal_code' => $address->postalCode,
                'shipping_area_id'     => $address->areaId,
                'shipping_latitude'    => $address->latitude,
                'shipping_longitude'   => $address->longitude,
                'coupon_code'          => $couponCode,
                'coupon_discount'      => $couponDiscount,
                'notes'                => $notes,
            ]);

            $orderId = (int) $this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO order_items (
                    order_id, product_id, variant_id,
                    product_name, variant_label, product_sku,
                    price, quantity, subtotal,
                    weight, length, width, height,
                    created_at, updated_at
                ) VALUES (
                    :order_id, :product_id, :variant_id,
                    :product_name, :variant_label, :product_sku,
                    :price, :quantity, :subtotal,
                    :weight, :length, :width, :height,
                    NOW(), NOW()
                )'
            );

            $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();

            foreach ($items as $item) {
                $itemStmt->execute([
                    'order_id'      => $orderId,
                    'product_id'    => $item->productId,
                    'variant_id'    => $item->variantId,
                    'product_name'  => $item->productName,
                    'variant_label' => $item->variantLabel,
                    'product_sku'   => $item->productSku,
                    'price'         => $item->price,
                    'quantity'      => $item->quantity,
                    'subtotal'      => $item->subtotal(),
                    'weight'        => $item->weight,
                    'length'        => $item->length,
                    'width'         => $item->width,
                    'height'        => $item->height,
                ]);

                $this->decreaseStock($item->variantId, $item->quantity, $orderId);

                // PENTING (race condition + fitur yang sebelumnya tidak
                // pernah jalan): tambah sold_count flash sale SECARA ATOMIC
                // di dalam transaksi order yang sama. Kalau produk ini
                // sedang flash sale dan kuotanya ternyata sudah habis
                // duluan (race dengan checkout lain yang barusan selesai),
                // increment ini gagal dan seluruh transaksi order di-
                // ROLLBACK — order tidak akan pernah terbentuk dengan harga
                // flash sale kalau stoknya sudah benar-benar habis.
                $incremented = $flashSaleService->incrementSoldCount($item->productId, $item->quantity);

                if (! $incremented) {
                    throw new ValidationException([
                        'flash_sale' => "Stok flash sale untuk \"{$item->productName}\" baru saja habis. Silakan hapus item ini dari keranjang atau checkout ulang.",
                    ]);
                }
            }

            $this->addStatusHistory($orderId, 'pending', 'Order dibuat', null, 'system');

            $this->pdo->commit();

            $cartService->clearCart();

            return $this->findById($orderId);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        if (! $order) {
            throw new RuntimeException('Order tidak ditemukan.');
        }

        $order['items'] = $this->getOrderItems($id);

        return $order;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE order_number = :order_number LIMIT 1');
        $stmt->execute(['order_number' => $orderNumber]);
        $order = $stmt->fetch();

        if (! $order) {
            return null;
        }

        $order['items'] = $this->getOrderItems((int) $order['id']);

        return $order;
    }

    public function findByUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM orders WHERE user_id = :user_id AND deleted_at IS NULL
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateStatus(int $orderId, string $status, ?string $note = null, ?int $byUserId = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $orderId]);

        $this->addStatusHistory($orderId, $status, $note, $byUserId);
    }

    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    public function getStatusHistory(int $orderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT osh.*, u.name AS created_by_name
             FROM order_status_histories osh
             LEFT JOIN users u ON u.id = osh.created_by
             WHERE osh.order_id = :order_id
             ORDER BY osh.created_at ASC'
        );
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    /**
     * Batalkan order (dipakai kalau payment gagal dibuat setelah order
     * sukses dibuat, supaya stok balik dan order tidak nyangkut sebagai
     * "pending"/"waiting_payment" terus-terusan).
     *
     * SEKARANG JUGA mengembalikan sold_count flash sale untuk tiap item
     * yang harganya cocok dengan sale_price flash sale aktif — sebelumnya
     * hanya stok fisik (product_variants.stock) yang dikembalikan, tapi
     * sold_count flash sale dibiarkan "nyangkut" seolah masih terjual
     * walau order-nya sudah batal, sehingga kuota flash sale salah hitung.
     */
    public function cancelOrder(int $orderId, string $reason = 'Pembayaran gagal dibuat'): void
    {
        $order = $this->findById($orderId);

        $this->pdo->beginTransaction();

        try {
            $inventoryService  = new \App\Modules\Inventory\Application\Services\InventoryService();
            $flashSaleService  = new \App\Modules\FlashSale\Application\Services\FlashSaleService();

            foreach ($order['items'] as $item) {
                $inventoryService->recordMovement(
                    (int) $item['variant_id'],
                    'in',
                    (int) $item['quantity'],
                    'order_cancelled',
                    $orderId,
                    null,
                    'Stok dikembalikan karena order dibatalkan'
                );

                // Kembalikan sold_count flash sale kalau item ini memang
                // dibeli dengan harga flash sale (cocokkan by price, sama
                // seperti logika command recalculate historis).
                $flashSaleService->decrementSoldCount((int) $item['product_id'], (int) $item['quantity'], (float) $item['price']);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE orders SET status = "cancelled", updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute(['id' => $orderId]);

            $this->addStatusHistory($orderId, 'cancelled', $reason, null, 'system');

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Dipakai dari PaymentService::handleCallback() saat order jadi
     * cancelled/expired SETELAH verifikasi API — mengembalikan stok +
     * sold_count tanpa perlu ulang findById()+beginTransaction() eksternal,
     * versi ringan dari cancelOrder() untuk dipanggil dari luar konteks
     * transaksi order (order sudah di-update statusnya oleh pemanggil).
     */
    public function restoreStockOnly(int $orderId): void
    {
        $items = $this->getOrderItems($orderId);

        $inventoryService = new \App\Modules\Inventory\Application\Services\InventoryService();
        $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();

        foreach ($items as $item) {
            $inventoryService->recordMovement(
                (int) $item['variant_id'],
                'in',
                (int) $item['quantity'],
                'order_cancelled',
                $orderId,
                null,
                'Stok dikembalikan (order dibatalkan via webhook payment)'
            );

            $flashSaleService->decrementSoldCount((int) $item['product_id'], (int) $item['quantity'], (float) $item['price']);
        }
    }

    /**
     * Cari order user yang masih nunggu pembayaran dan belum expired,
     * dipakai supaya checkout ulang gak bikin order/payment baru terus.
     */
    public function findActivePendingOrder(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.* FROM orders o
             INNER JOIN payments p ON p.order_id = o.id
             WHERE o.user_id = :user_id
               AND o.status = 'waiting_payment'
               AND p.status = 'pending'
               AND p.expired_at > NOW()
             ORDER BY o.id DESC LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $order = $stmt->fetch();

        if (! $order) {
            return null;
        }

        $order['items'] = $this->getOrderItems((int) $order['id']);

        return $order;
    }

    private function addStatusHistory(int $orderId, string $status, ?string $note = null, ?int $byUserId = null, string $source = 'manual'): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO order_status_histories (order_id, status, note, source, created_by, created_at, updated_at)
             VALUES (:order_id, :status, :note, :source, :created_by, NOW(), NOW())'
        );
        $stmt->execute([
            'order_id'   => $orderId,
            'status'     => $status,
            'note'       => $note,
            'source'     => $source,
            'created_by' => $byUserId,
        ]);
    }

    private function decreaseStock(int $variantId, int $quantity, int $orderId): void
    {
        $inventoryService = new \App\Modules\Inventory\Application\Services\InventoryService();
        $inventoryService->recordMovement(
            $variantId,
            'out',
            $quantity,
            'order_placed',
            $orderId,
            null,
            'Stok berkurang karena order baru'
        );
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));
    }
}
