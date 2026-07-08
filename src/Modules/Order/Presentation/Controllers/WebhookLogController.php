<?php

declare(strict_types=1);

namespace App\Modules\Order\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class WebhookLogController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(Request $request): Response
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 30;
        $source  = (string) $request->query('source', '');
        $status  = (string) $request->query('status', '');
        $offset  = ($page - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];

        if ($source !== '') {
            $where[] = 'source = :source';
            $params['source'] = $source;
        }

        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM webhook_logs WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhook_logs WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        return Response::make(view('Order::admin.webhook-logs', [
            'logs'         => $logs,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'sourceFilter' => $source,
            'statusFilter' => $status,
        ]));
    }
}