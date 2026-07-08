<?php

declare(strict_types=1);

namespace App\Modules\Review\Application\Services;

use App\Core\Exceptions\ValidationException;
use PDO;
use RuntimeException;

class ReviewService
{
    private PDO $pdo;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Ambil daftar order_item milik user yang BELUM direview dan BISA direview
     * (order sudah completed).
     */
    public function getReviewableItems(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT oi.id AS order_item_id, oi.product_id, oi.product_name, oi.variant_label,
                    o.order_number, o.created_at AS order_date,
                    (SELECT pi.path FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = :user_id
             AND o.status = "completed"
             AND oi.id NOT IN (SELECT order_item_id FROM reviews)
             ORDER BY o.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Buat review baru. Validasi: order_item harus milik user dan order-nya completed,
     * serta belum pernah direview.
     */
    public function create(int $userId, int $orderItemId, int $rating, ?string $comment, ?array $images = null): int
    {
        if ($rating < 1 || $rating > 5) {
            throw new ValidationException(['rating' => 'Rating harus antara 1-5.']);
        }

        // Validasi kepemilikan & status order
        $stmt = $this->pdo->prepare(
            'SELECT oi.id, oi.product_id, o.user_id, o.status
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.id = :order_item_id LIMIT 1'
        );
        $stmt->execute(['order_item_id' => $orderItemId]);
        $item = $stmt->fetch();

        if (! $item) {
            throw new RuntimeException('Item order tidak ditemukan.');
        }

        if ((int) $item['user_id'] !== $userId) {
            throw new RuntimeException('Anda tidak berhak mereview item ini.');
        }

        if ($item['status'] !== 'completed') {
            throw new ValidationException(['order' => 'Order harus berstatus selesai sebelum bisa direview.']);
        }

        // Cek sudah pernah direview
        $existing = $this->pdo->prepare('SELECT id FROM reviews WHERE order_item_id = :order_item_id LIMIT 1');
        $existing->execute(['order_item_id' => $orderItemId]);
        if ($existing->fetch()) {
            throw new ValidationException(['review' => 'Item ini sudah pernah direview.']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO reviews (product_id, user_id, order_item_id, rating, comment, created_at, updated_at)
             VALUES (:product_id, :user_id, :order_item_id, :rating, :comment, NOW(), NOW())'
        );
        $stmt->execute([
            'product_id'    => $item['product_id'],
            'user_id'       => $userId,
            'order_item_id' => $orderItemId,
            'rating'        => $rating,
            'comment'       => $comment,
        ]);

        $reviewId = (int) $this->pdo->lastInsertId();

        if ($images) {
            $this->handleImageUploads($reviewId, $images);
        }

        return $reviewId;
    }

    private function handleImageUploads(int $reviewId, array $files): void
    {
        if (empty($files['name'][0])) {
            return;
        }

        $uploadDir = base_path('storage/uploads/reviews');

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $count = count($files['name']);

        for ($i = 0; $i < min($count, 5); $i++) { // maks 5 foto per review
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if (! in_array($files['type'][$i], self::ALLOWED_MIME, true)) {
                continue;
            }

            if ($files['size'][$i] > self::MAX_SIZE_BYTES) {
                continue;
            }

            $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $filename  = uniqid('review_', true) . '.' . $extension;
            $dest      = $uploadDir . '/' . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO review_images (review_id, path, created_at, updated_at)
                     VALUES (:review_id, :path, NOW(), NOW())'
                );
                $stmt->execute(['review_id' => $reviewId, 'path' => 'reviews/' . $filename]);
            }
        }
    }

    /**
     * Ambil semua review untuk satu produk + ringkasan rating.
     */
    public function getByProduct(int $productId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id
             ORDER BY r.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();

        // Ambil gambar per review
        foreach ($reviews as &$review) {
            $imgStmt = $this->pdo->prepare('SELECT path FROM review_images WHERE review_id = :review_id');
            $imgStmt->execute(['review_id' => $review['id']]);
            $review['images'] = array_column($imgStmt->fetchAll(), 'path');
        }

        return $reviews;
    }

    /**
     * Ringkasan rating produk: rata-rata, total review, distribusi bintang.
     */
    public function getSummary(int $productId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                COALESCE(AVG(rating), 0) AS average,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS star5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS star4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS star3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS star2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS star1
             FROM reviews WHERE product_id = :product_id'
        );
        $stmt->execute(['product_id' => $productId]);
        $row = $stmt->fetch();

        return [
            'total'   => (int) $row['total'],
            'average' => round((float) $row['average'], 1),
            'distribution' => [
                5 => (int) $row['star5'],
                4 => (int) $row['star4'],
                3 => (int) $row['star3'],
                2 => (int) $row['star2'],
                1 => (int) $row['star1'],
            ],
        ];
    }

    public function countByProduct(int $productId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);

        return (int) $stmt->fetchColumn();
    }
}