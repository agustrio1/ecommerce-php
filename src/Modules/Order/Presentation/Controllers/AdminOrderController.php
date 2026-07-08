<?php

declare(strict_types=1);

namespace App\Modules\Order\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Order\Application\Services\OrderService;
use App\Modules\Payment\Application\Services\PaymentService;
use App\Modules\Shipping\Infrastructure\Http\BiteshipClient;
use App\Modules\Setting\Application\Services\SettingService;
use PDO;
use RuntimeException;

class AdminOrderController
{
    private OrderService $orderService;
    private PaymentService $paymentService;
    private PDO $pdo;
    private SettingService $setting;

    public function __construct()
    {
        $this->orderService   = new OrderService();
        $this->paymentService = new PaymentService();
        $this->pdo            = db();
        $this->setting        = SettingService::getInstance();
    }

    /**
     * List semua order dengan filter & pagination.
     */
    public function index(Request $request): Response
    {
        $page     = max(1, (int) $request->query('page', 1));
        $perPage  = 20;
        $search   = (string) $request->query('search', '');
        $status   = (string) $request->query('status', '');
        $offset   = ($page - 1) * $perPage;

        $where  = ['o.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $where[] = '(o.order_number LIKE :search OR o.recipient_name LIKE :search OR o.recipient_phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '') {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders o WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
                (SELECT p.status FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_status,
                (SELECT s.waybill_id FROM shipments s WHERE s.order_id = o.id ORDER BY s.id DESC LIMIT 1) AS waybill_id
             FROM orders o
             WHERE {$whereSql}
             ORDER BY o.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        return Response::make(view('Order::admin.index', [
            'orders'       => $orders,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'search'       => $search,
            'statusFilter' => $status,
            'statuses'     => $this->getStatusList(),
        ]));
    }

    /**
     * Detail order admin — info lengkap, payment, shipment, history.
     */
    public function show(Request $request, string $id): Response
    {
        $order   = $this->orderService->findById((int) $id);
        $payment = $this->paymentService->findByOrderId((int) $id);
        $history = $this->orderService->getStatusHistory((int) $id);

        // Cek shipment
        $shipStmt = $this->pdo->prepare('SELECT * FROM shipments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1');
        $shipStmt->execute(['order_id' => $id]);
        $shipment = $shipStmt->fetch() ?: null;

        // Tracking (jika ada waybill)
        $tracking = null;
        if ($shipment && $shipment['biteship_tracking_id']) {
            try {
                $client   = new BiteshipClient();
                $tracking = $client->getTracking($shipment['biteship_tracking_id']);
            } catch (\Throwable $e) {
                // Gagal ambil tracking, tidak perlu crash
                $tracking = null;
            }
        }

        return Response::make(view('Order::admin.show', [
            'order'    => $order,
            'payment'  => $payment,
            'history'  => $history,
            'shipment' => $shipment,
            'tracking' => $tracking,
            'statuses' => $this->getStatusList(),
        ]));
    }

    /**
     * Update status order manual.
     */
    public function updateStatus(Request $request, string $id): Response
    {
        $newStatus = (string) $request->input('status');
        $note      = $request->input('note');
        $userId    = CurrentUserService::user()?->id;

        $validStatuses = array_keys($this->getStatusList());

        if (! in_array($newStatus, $validStatuses, true)) {
            Session::flash('error', 'Status tidak valid.');
            return Response::redirect("/admin/orders/{$id}");
        }

        $this->orderService->updateStatus((int) $id, $newStatus, $note, $userId);

        Session::flash('success', 'Status order berhasil diperbarui.');

        return Response::redirect("/admin/orders/{$id}");
    }

    /**
     * Buat shipment Biteship dari order.
     * Ini akan call Biteship Orders API dan simpan hasilnya.
     */
    public function createShipment(Request $request, string $id): Response
    {
        $order = $this->orderService->findById((int) $id);

        // Validasi: order harus sudah paid
        if (! in_array($order['status'], ['paid', 'processing'], true)) {
            Session::flash('error', 'Order harus berstatus "Dibayar" atau "Diproses" untuk membuat pengiriman.');
            return Response::redirect("/admin/orders/{$id}");
        }

        // Cek sudah ada shipment belum
        $existShip = $this->pdo->prepare('SELECT id FROM shipments WHERE order_id = :order_id LIMIT 1');
        $existShip->execute(['order_id' => $id]);
        if ($existShip->fetch()) {
            Session::flash('error', 'Shipment untuk order ini sudah dibuat sebelumnya.');
            return Response::redirect("/admin/orders/{$id}");
        }

        try {
            $client = new BiteshipClient();

            // Build items dari order_items
            $biteshipItems = array_map(fn ($item) => [
                'name'        => $item['product_name'],
                'description' => $item['variant_label'] ?? '',
                'value'       => (int) $item['price'],
                'quantity'    => (int) $item['quantity'],
                'weight'      => (int) max(1, ($item['weight'] ?? 100) * $item['quantity']),
                'length'      => (int) ($item['length'] ?: 10),
                'width'       => (int) ($item['width'] ?: 10),
                'height'      => (int) ($item['height'] ?: 10),
            ], $order['items']);

            // Build origin dari setting toko
            $originAreaId    = $this->setting->biteshipOriginAreaId();
            $originPostal    = $this->setting->storePostalCode();

            $originParams = [];
            if ($originAreaId) {
                $originParams['origin_area_id'] = $originAreaId;
            } elseif ($originPostal) {
                $originParams['origin_postal_code'] = (int) $originPostal;
            }

            // Build destination dari data order
            $destParams = [];
            if ($order['shipping_area_id']) {
                $destParams['destination_area_id'] = $order['shipping_area_id'];
            } elseif ($order['shipping_postal_code']) {
                $destParams['destination_postal_code'] = (int) $order['shipping_postal_code'];
            }

            $biteshipParams = array_merge([
                'origin_contact_name'        => $this->setting->storeName(),
                'origin_contact_phone'        => $this->setting->storePhone(),
                'origin_address'              => $this->setting->storeAddress(),
                'destination_contact_name'    => $order['recipient_name'],
                'destination_contact_phone'   => $order['recipient_phone'],
                'destination_address'         => implode(', ', array_filter([
                    $order['shipping_address'],
                    $order['shipping_district'],
                    $order['shipping_city'],
                    $order['shipping_province'],
                ])),
                'courier_company'             => $order['courier_company'],
                'courier_type'                => $order['courier_type'],
                'delivery_type'               => 'now',
                'order_note'                  => $order['notes'] ?? '',
                'metadata'                    => ['order_number' => $order['order_number']],
                'items'                       => $biteshipItems,
            ], $originParams, $destParams);

            $biteshipResponse = $client->createOrder($biteshipParams);

            // Simpan ke tabel shipments
            $stmt = $this->pdo->prepare(
                'INSERT INTO shipments (
                    order_id,
                    courier_company, courier_type, courier_service_name,
                    biteship_order_id, biteship_tracking_id,
                    waybill_id, courier_tracking_link,
                    origin_contact_name, origin_contact_phone, origin_address,
                    origin_postal_code, origin_area_id,
                    destination_contact_name, destination_contact_phone, destination_address,
                    destination_postal_code, destination_area_id,
                    cost, weight, status,
                    raw_response, created_at, updated_at
                ) VALUES (
                    :order_id,
                    :courier_company, :courier_type, :courier_service_name,
                    :biteship_order_id, :biteship_tracking_id,
                    :waybill_id, :courier_tracking_link,
                    :origin_contact_name, :origin_contact_phone, :origin_address,
                    :origin_postal_code, :origin_area_id,
                    :destination_contact_name, :destination_contact_phone, :destination_address,
                    :destination_postal_code, :destination_area_id,
                    :cost, :weight, :status,
                    :raw_response, NOW(), NOW()
                )'
            );

            $stmt->execute([
                'order_id'                => $id,
                'courier_company'         => $order['courier_company'],
                'courier_type'            => $order['courier_type'],
                'courier_service_name'    => $order['courier_service_name'] ?? '',
                'biteship_order_id'       => $biteshipResponse['id'] ?? null,
                'biteship_tracking_id'    => $biteshipResponse['courier']['tracking_id'] ?? null,
                'waybill_id'              => $biteshipResponse['courier']['waybill_id'] ?? null,
                'courier_tracking_link'   => $biteshipResponse['courier']['link'] ?? null,
                'origin_contact_name'     => $this->setting->storeName(),
                'origin_contact_phone'    => $this->setting->storePhone(),
                'origin_address'          => $this->setting->storeAddress(),
                'origin_postal_code'      => $originPostal ?: null,
                'origin_area_id'          => $originAreaId ?: null,
                'destination_contact_name'  => $order['recipient_name'],
                'destination_contact_phone' => $order['recipient_phone'],
                'destination_address'       => $order['shipping_address'],
                'destination_postal_code'   => $order['shipping_postal_code'],
                'destination_area_id'       => $order['shipping_area_id'],
                'cost'                    => $biteshipResponse['price'] ?? $order['shipping_cost'],
                'weight'                  => $biteshipResponse['courier']['tracking_id'] ? null : null,
                'status'                  => $biteshipResponse['status'] ?? 'confirmed',
                'raw_response'            => json_encode($biteshipResponse),
            ]);

            // Update status order ke "shipped" (atau "processing" kalau belum ada waybill)
            $newOrderStatus = ($biteshipResponse['courier']['waybill_id'] ?? null) ? 'shipped' : 'processing';
            $this->orderService->updateStatus(
                (int) $id,
                $newOrderStatus,
                'Shipment dibuat via Biteship. Order ID: ' . ($biteshipResponse['id'] ?? '-'),
                CurrentUserService::user()?->id
            );

            Session::flash('success', 'Shipment berhasil dibuat via Biteship. ' . ($biteshipResponse['courier']['waybill_id'] ? 'Waybill: ' . $biteshipResponse['courier']['waybill_id'] : 'Waybill akan menyusul.'));
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal buat shipment Biteship: ' . $e->getMessage());
        }

        return Response::redirect("/admin/orders/{$id}");
    }

    /**
     * Webhook Biteship — update status shipment & order otomatis.
     * Events: order.status, order.price, order.waybill_id
     */
    public function biteshipWebhook(Request $request): Response
    {
        $logger = new \App\Core\Support\WebhookLogger();
        $data   = $request->json();

        if (empty($data)) {
            return Response::make('No data', 400);
        }

        $event           = $data['event'] ?? null;
        $biteshipOrderId = $data['order_id'] ?? null;

        // Reference unik per event supaya idempotency check akurat
        // (event yang sama untuk order yang sama tidak diproses dua kali)
        $reference = $biteshipOrderId ? $biteshipOrderId . ':' . $event : null;

        $logId = $logger->log('biteship', $event, $reference, $data);

        if ($reference && $logger->alreadyProcessed('biteship', $reference)) {
            $logger->markProcessed($logId);
            return Response::make('OK (already processed)', 200);
        }

        if (! $biteshipOrderId) {
            $logger->markFailed($logId, 'Missing order_id');
            return Response::make('Missing order_id', 400);
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT s.*, o.id AS app_order_id FROM shipments s
                 JOIN orders o ON o.id = s.order_id
                 WHERE s.biteship_order_id = :biteship_order_id LIMIT 1'
            );
            $stmt->execute(['biteship_order_id' => $biteshipOrderId]);
            $shipment = $stmt->fetch();

            if (! $shipment) {
                throw new RuntimeException('Shipment not found for biteship_order_id: ' . $biteshipOrderId);
            }

            $update = [];
            $newOrderStatus = null;

            switch ($event) {
                case 'order.status':
                    $biteshipStatus = $data['status'] ?? null;
                    if ($biteshipStatus) {
                        $update['status'] = $biteshipStatus;
                        $newOrderStatus = match ($biteshipStatus) {
                            'picking_up', 'picked'      => 'processing',
                            'dropping_off'               => 'shipped',
                            'delivered'                   => 'delivered',
                            'rejected', 'returned'       => 'cancelled',
                            default                       => null,
                        };
                    }
                    break;

                case 'order.price':
                    $actualPrice = $data['actual_price'] ?? null;
                    if ($actualPrice !== null) {
                        $update['actual_cost'] = $actualPrice;
                    }
                    break;

                case 'order.waybill_id':
                    $waybillId = $data['waybill_id'] ?? null;
                    if ($waybillId) {
                        $update['waybill_id'] = $waybillId;
                        $update['shipped_at'] = date('Y-m-d H:i:s');
                        $newOrderStatus = 'shipped';
                    }
                    break;
            }

            if (! empty($update)) {
                $setClauses = implode(', ', array_map(fn ($k) => "`{$k}` = :{$k}", array_keys($update)));
                $updateStmt = $this->pdo->prepare(
                    "UPDATE shipments SET {$setClauses}, updated_at = NOW() WHERE id = :id"
                );
                $update['id'] = $shipment['id'];
                $updateStmt->execute($update);
            }

            if ($newOrderStatus) {
                $this->orderService->updateStatus(
                    (int) $shipment['app_order_id'],
                    $newOrderStatus,
                    "Biteship webhook: {$event}",
                    null,
                );
            }

            $logger->markProcessed($logId);

            return Response::make('OK', 200);
        } catch (\Throwable $e) {
            $logger->markFailed($logId, $e->getMessage());
            error_log('Biteship webhook error: ' . $e->getMessage());

            return Response::make('Error logged: ' . $e->getMessage(), 200);
        }
    }

    private function getStatusList(): array
    {
        return [
            'pending'         => 'Menunggu',
            'waiting_payment' => 'Menunggu Pembayaran',
            'paid'            => 'Dibayar',
            'processing'      => 'Diproses',
            'shipped'         => 'Dikirim',
            'delivered'       => 'Terkirim',
            'completed'       => 'Selesai',
            'cancelled'       => 'Dibatalkan',
            'refunded'        => 'Direfund',
        ];
    }
}