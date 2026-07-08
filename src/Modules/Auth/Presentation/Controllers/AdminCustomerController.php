<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use PDO;

class AdminCustomerController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(Request $request): Response
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $search  = (string) $request->query('search', '');
        $offset  = ($page - 1) * $perPage;

        $where  = ['r.slug = "customer"'];
        $params = [];

        if ($search !== '') {
            $where[]          = '(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.deleted_at IS NULL) AS total_orders,
                (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.deleted_at IS NULL AND o.status NOT IN ('cancelled','refunded')) AS total_spent
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE {$whereSql}
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll();

        return Response::make(view('Auth::admin.customers', [
            'title'     => 'Pelanggan',
            'customers' => $customers,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'search'    => $search,
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        $stmt = $this->pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();

        if (! $customer) {
            return Response::notFound('Pelanggan tidak ditemukan.');
        }

        $orders = $this->pdo->prepare(
            'SELECT * FROM orders WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 10'
        );
        $orders->execute(['user_id' => $id]);
        $orderList = $orders->fetchAll();

        return Response::make(view('Auth::admin.customer-detail', [
            'title'    => 'Detail Pelanggan',
            'customer' => $customer,
            'orders'   => $orderList,
        ]));
    }
}