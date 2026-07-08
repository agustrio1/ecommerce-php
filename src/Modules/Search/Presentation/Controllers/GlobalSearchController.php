<?php

declare(strict_types=1);

namespace App\Modules\Search\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class GlobalSearchController
{
    private PDO $pdo;

    private const LIMIT_PER_GROUP = 5;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function search(Request $request): Response
    {
        try {
            $query = trim((string) $request->input('q', ''));

            if ($query === '' || mb_strlen($query) < 2) {
                return Response::json([
                    'query'   => $query,
                    'results' => [
                        'products'   => [],
                        'orders'     => [],
                        'customers'  => [],
                        'categories' => [],
                    ],
                ]);
            }

            $like = '%' . $query . '%';

            return Response::json([
                'query'   => $query,
                'results' => [
                    'products'   => $this->searchProducts($like),
                    'orders'     => $this->searchOrders($like),
                    'customers'  => $this->searchCustomers($like),
                    'categories' => $this->searchCategories($like),
                ],
            ]);
        } catch (\Throwable $e) {
            return Response::json([
                'error'   => true,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    private function searchProducts(string $like): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, sku
             FROM products
             WHERE deleted_at IS NULL
               AND (name LIKE :like1 OR sku LIKE :like2)
             ORDER BY name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':like1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', self::LIMIT_PER_GROUP, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn ($row) => [
            'id'       => (int) $row['id'],
            'title'    => $row['name'],
            'subtitle' => $row['sku'] ? 'SKU: ' . $row['sku'] : null,
            'url'      => '/admin/products/' . $row['id'] . '/edit',
        ], $stmt->fetchAll());
    }

    private function searchOrders(string $like): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.order_number, o.status, u.name AS customer_name
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.order_number LIKE :like1 OR u.name LIKE :like2
             ORDER BY o.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':like1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', self::LIMIT_PER_GROUP, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn ($row) => [
            'id'       => (int) $row['id'],
            'title'    => $row['order_number'],
            'subtitle' => trim(($row['customer_name'] ?? '-') . ' · ' . ucfirst((string) $row['status'])),
            'url'      => '/admin/orders/' . $row['id'],
        ], $stmt->fetchAll());
    }

    private function searchCustomers(string $like): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email
             FROM users
             WHERE name LIKE :like1 OR email LIKE :like2
             ORDER BY name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':like1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', self::LIMIT_PER_GROUP, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn ($row) => [
            'id'       => (int) $row['id'],
            'title'    => $row['name'],
            'subtitle' => $row['email'],
            'url'      => '/admin/customers/' . $row['id'],
        ], $stmt->fetchAll());
    }

    private function searchCategories(string $like): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name
             FROM categories
             WHERE name LIKE :like
             ORDER BY name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', self::LIMIT_PER_GROUP, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn ($row) => [
            'id'       => (int) $row['id'],
            'title'    => $row['name'],
            'subtitle' => null,
            'url'      => '/admin/categories/' . $row['id'] . '/edit',
        ], $stmt->fetchAll());
    }
}