<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Application\Services;

use PDO;

class WishlistService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.*, p.name AS product_name, p.slug, p.price, p.compare_price, p.status,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM wishlists w
             JOIN products p ON p.id = w.product_id
             WHERE w.user_id = :user_id AND p.deleted_at IS NULL
             ORDER BY w.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function isWishlisted(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM wishlists WHERE user_id = :uid AND product_id = :pid LIMIT 1');
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
        return (bool) $stmt->fetch();
    }

    public function toggle(int $userId, int $productId): bool
    {
        if ($this->isWishlisted($userId, $productId)) {
            $this->pdo->prepare('DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid')
                ->execute(['uid' => $userId, 'pid' => $productId]);
            return false; // dihapus
        }

        $this->pdo->prepare(
            'INSERT INTO wishlists (user_id, product_id, created_at, updated_at) VALUES (:uid, :pid, NOW(), NOW())'
        )->execute(['uid' => $userId, 'pid' => $productId]);
        return true; // ditambahkan
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}