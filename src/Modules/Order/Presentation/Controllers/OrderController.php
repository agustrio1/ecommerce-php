<?php

declare(strict_types=1);

namespace App\Modules\Order\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Order\Application\Services\OrderService;
use App\Modules\Payment\Application\Services\PaymentService;

class OrderController
{
    private OrderService $orderService;
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->orderService   = new OrderService();
        $this->paymentService = new PaymentService();
    }

    /**
     * Halaman detail order + instruksi pembayaran.
     */
    public function show(Request $request, string $orderNumber): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $order = $this->orderService->findByOrderNumber($orderNumber);

        if (! $order || (int) $order['user_id'] !== $user->id) {
            return Response::notFound('Order tidak ditemukan.');
        }

        $payment = $this->paymentService->findByOrderId((int) $order['id']);
        $history = $this->orderService->getStatusHistory((int) $order['id']);
        $isNew   = $request->query('new') === '1';

        return Response::make(view('storefront.order-detail', [
            'title'   => 'Order ' . $orderNumber,
            'order'   => $order,
            'payment' => $payment,
            'history' => $history,
            'isNew'   => $isNew,
        ]));
    }

    /**
     * Daftar order milik user yang login.
     */
    public function myOrders(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $page   = max(1, (int) $request->query('page', 1));
        $orders = $this->orderService->findByUser($user->id, $page);

        return Response::make(view('storefront.my-orders', [
            'title'  => 'Pesanan Saya',
            'orders' => $orders,
            'page'   => $page,
        ]));
    }

    /**
     * Webhook callback dari iPaymu.
     * POST /webhooks/ipaymu
     */
    public function ipaymuCallback(Request $request): Response
    {
        $logger = new \App\Core\Support\WebhookLogger();
        $data   = $request->all();

        if (empty($data)) {
            $data = $request->json();
        }

        $reference = $data['trx_id'] ?? $data['reference_id'] ?? $data['payment_no'] ?? null;

        $logId = $logger->log('ipaymu', $data['status'] ?? null, $reference ? (string) $reference : null, $data);

        // Idempotency: kalau reference ini sudah pernah sukses diproses, skip (cegah double processing)
        if ($reference && $logger->alreadyProcessed('ipaymu', (string) $reference)) {
            $logger->markProcessed($logId);
            return Response::make('OK (already processed)', 200);
        }

        try {
            $this->paymentService->handleCallback($data);
            $logger->markProcessed($logId);

            return Response::make('OK', 200);
        } catch (\Throwable $e) {
            $logger->markFailed($logId, $e->getMessage());
            error_log('iPaymu callback error: ' . $e->getMessage());

            // Tetap return 200 supaya iPaymu tidak retry terus untuk error permanen
            return Response::make('Error logged: ' . $e->getMessage(), 200);
        }
    }
}