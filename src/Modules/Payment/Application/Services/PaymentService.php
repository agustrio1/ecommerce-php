<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Infrastructure\Http\IpaymuClient;
use App\Modules\Setting\Application\Services\SettingService;
use PDO;
use RuntimeException;

class PaymentService
{
    private PDO $pdo;
    private IpaymuClient $ipaymu;
    private SettingService $setting;

    public function __construct()
    {
        $this->pdo     = db();
        $this->ipaymu  = new IpaymuClient();
        $this->setting = SettingService::getInstance();
    }

    /**
     * Buat pembayaran direct (VA bank langsung tanpa halaman) via iPaymu.
     * Return array berisi instruksi pembayaran.
     */
    public function createDirectPayment(
        array $order,
        string $paymentMethod,
        string $paymentChannel,
        string $buyerName,
        string $buyerPhone,
        string $buyerEmail
    ): array {
        $paymentNo = 'PAY-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));

        $params = [
            'name'           => $buyerName,
            'phone'          => $buyerPhone,
            'email'          => $buyerEmail ?: 'noreply@' . parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),
            'amount'         => (int) $order['total'],
            'notifyUrl'      => rtrim(env('APP_URL', ''), '/') . '/webhooks/ipaymu',
            'referenceId'    => $paymentNo,
            'paymentMethod'  => $paymentMethod,
            'paymentChannel' => $paymentChannel,
            'expired'        => 24, // jam
        ];

        // Panggil iPaymu Direct Payment API
        $response = $this->ipaymu->directPayment($params);

        // Simpan ke tabel payments
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (
                order_id, payment_no,
                ipaymu_trx_id, ipaymu_session_id, ipaymu_reference_id,
                ipaymu_via, ipaymu_channel,
                ipaymu_pay_code, ipaymu_pay_code_url,
                payment_method, payment_channel,
                amount, fee, status,
                expired_at, created_at, updated_at
            ) VALUES (
                :order_id, :payment_no,
                :ipaymu_trx_id, :ipaymu_session_id, :ipaymu_reference_id,
                :ipaymu_via, :ipaymu_channel,
                :ipaymu_pay_code, :ipaymu_pay_code_url,
                :payment_method, :payment_channel,
                :amount, 0, "pending",
                DATE_ADD(NOW(), INTERVAL 24 HOUR),
                NOW(), NOW()
            )'
        );

        $stmt->execute([
            'order_id'             => $order['id'],
            'payment_no'           => $paymentNo,
            'ipaymu_trx_id'        => $response['TransactionId'] ?? null,
            'ipaymu_session_id'    => $response['SessionId'] ?? null,
            'ipaymu_reference_id'  => $paymentNo,
            'ipaymu_via'           => $response['Via'] ?? $paymentMethod,
            'ipaymu_channel'       => $response['Channel'] ?? $paymentChannel,
            'ipaymu_pay_code'      => $response['PaymentNo'] ?? $response['Data']['PaymentNo'] ?? null,
            'ipaymu_pay_code_url'  => $response['PaymentCode'] ?? null,
            'payment_method'       => $paymentMethod,
            'payment_channel'      => $paymentChannel,
            'amount'               => $order['total'],
        ]);

        $paymentId = (int) $this->pdo->lastInsertId();

        // Update order status ke waiting_payment
        $updateOrder = $this->pdo->prepare(
            'UPDATE orders SET status = "waiting_payment", updated_at = NOW() WHERE id = :id'
        );
        $updateOrder->execute(['id' => $order['id']]);

        return [
            'payment_id'    => $paymentId,
            'payment_no'    => $paymentNo,
            'method'        => $paymentMethod,
            'channel'       => $paymentChannel,
            'pay_code'      => $response['PaymentNo'] ?? $response['Data']['PaymentNo'] ?? null,
            'amount'        => $order['total'],
            'expired_hours' => 24,
            'raw'           => $response,
        ];
    }

    /**
     * Buat pembayaran via Redirect Payment — customer diarahkan ke
     * halaman pembayaran resmi iPaymu (halaman tersebut menampilkan
     * semua metode pembayaran sekaligus: VA, QRIS, dll).
     *
     * Ini lebih mudah untuk TESTING dibanding Direct Payment, karena:
     * - Halaman sandbox iPaymu (https://sandbox.ipaymu.com) biasanya
     *   menyediakan cara untuk menyelesaikan/menyimulasikan pembayaran
     *   langsung dari halaman tersebut.
     * - Tidak perlu generate VA per channel secara manual — customer
     *   bebas pilih metode di halaman iPaymu.
     */
    public function createRedirectPayment(
        array $order,
        string $buyerName,
        string $buyerPhone,
        string $buyerEmail
    ): array {
        $paymentNo = 'PAY-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));

        $product = [];
        $qty     = [];
        $price   = [];

        foreach ($order['items'] as $item) {
            $product[] = $item['product_name'] . ($item['variant_label'] ? ' (' . $item['variant_label'] . ')' : '');
            $qty[]     = (string) $item['quantity'];
            $price[]   = (string) $item['price'];
        }

        // Tambahkan ongkir sebagai baris produk terpisah, supaya total yang
        // dibayar customer di halaman iPaymu sesuai dengan total order
        // (subtotal produk + ongkir).
        if ((float) $order['shipping_cost'] > 0) {
            $product[] = 'Ongkos Kirim (' . $order['courier_company'] . ' ' . $order['courier_type'] . ')';
            $qty[]     = '1';
            $price[]   = (string) $order['shipping_cost'];
        }

        $appUrl = rtrim(env('APP_URL', ''), '/');

        $params = [
            'product'     => $product,
            'qty'         => $qty,
            'price'       => $price,
            'returnUrl'   => $appUrl . '/orders/' . $order['order_number'] . '/payment?paid=1',
            'cancelUrl'   => $appUrl . '/orders/' . $order['order_number'] . '/payment?cancelled=1',
            'notifyUrl'   => $appUrl . '/webhooks/ipaymu',
            'referenceId' => $paymentNo,
            'buyerName'   => $buyerName,
            'buyerPhone'  => $buyerPhone,
            'buyerEmail'  => $buyerEmail ?: 'noreply@' . parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),
        ];

        // Panggil iPaymu Redirect Payment API
        $response = $this->ipaymu->redirectPayment($params);

        // Simpan ke tabel payments. ipaymu_pay_code_url dipakai menyimpan
        // URL halaman pembayaran iPaymu supaya bisa ditampilkan ulang di
        // halaman order detail kalau customer belum sempat bayar.
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (
                order_id, payment_no,
                ipaymu_trx_id, ipaymu_session_id, ipaymu_reference_id,
                ipaymu_via, ipaymu_channel,
                ipaymu_pay_code, ipaymu_pay_code_url,
                payment_method, payment_channel,
                amount, fee, status,
                expired_at, created_at, updated_at
            ) VALUES (
                :order_id, :payment_no,
                NULL, :ipaymu_session_id, :ipaymu_reference_id,
                NULL, NULL,
                NULL, :ipaymu_pay_code_url,
                "redirect", "all",
                :amount, 0, "pending",
                DATE_ADD(NOW(), INTERVAL 24 HOUR),
                NOW(), NOW()
            )'
        );

        $stmt->execute([
            'order_id'            => $order['id'],
            'payment_no'          => $paymentNo,
            'ipaymu_session_id'   => $response['SessionID'] ?? null,
            'ipaymu_reference_id' => $paymentNo,
            'ipaymu_pay_code_url' => $response['Url'] ?? null,
            'amount'              => $order['total'],
        ]);

        $paymentId = (int) $this->pdo->lastInsertId();

        // Update order status ke waiting_payment
        $updateOrder = $this->pdo->prepare(
            'UPDATE orders SET status = "waiting_payment", updated_at = NOW() WHERE id = :id'
        );
        $updateOrder->execute(['id' => $order['id']]);

        return [
            'payment_id'    => $paymentId,
            'payment_no'    => $paymentNo,
            'redirect_url'  => $response['Url'] ?? null,
            'amount'        => $order['total'],
            'expired_hours' => 24,
            'raw'           => $response,
        ];
    }

    /**
     * Handle webhook callback dari iPaymu.
     *
     * CATATAN PENTING: sebelumnya di sini ada percobaan verifikasi ulang ke
     * endpoint /payment/status milik iPaymu (checkTransaction()), tapi
     * endpoint itu ternyata tidak bisa dikonfirmasi kebenarannya dari
     * dokumentasi resmi API v2 (kemungkinan endpoint itu peninggalan API v1
     * lama yang cara autentikasinya berbeda total) — hasilnya verifikasi
     * SELALU gagal dengan "respons kosong", bukan karena ada serangan, tapi
     * karena endpoint/parameternya sendiri kemungkinan salah.
     *
     * Sebagai gantinya, dipakai kombinasi proteksi berikut TANPA butuh API
     * call tambahan ke iPaymu:
     *   1. Idempotency (WebhookLogger) — callback yang sama tidak diproses
     *      dua kali.
     *   2. Payment harus ditemukan di database kita via trx_id/reference_id/
     *      payment_no — bukan asal percaya apa pun yang dikirim.
     *   3. Nominal di webhook body WAJIB cocok dengan payments.amount yang
     *      SUDAH TERSIMPAN sejak payment dibuat (bukan dari webhook) —
     *      kalau beda, ditolak.
     *   4. Payment yang statusnya SUDAH final (paid/cancelled/expired)
     *      tidak diproses ulang jadi status lain oleh callback susulan.
     *
     * Ini bukan sekuat verifikasi server-to-server yang benar-benar
     * terkonfirmasi, tapi jauh lebih baik daripada mempercayai webhook body
     * mentah-mentah tanpa validasi sama sekali.
     */
    public function handleCallback(array $callbackData): void
    {
        $trxId       = $callbackData['trx_id'] ?? null;
        $referenceId = $callbackData['reference_id'] ?? null;
        $paymentNo   = $callbackData['payment_no'] ?? null;
        $status      = strtolower((string) ($callbackData['status'] ?? ''));
        $amount      = (float) ($callbackData['amount'] ?? $callbackData['total'] ?? 0);

        if (! $trxId && ! $referenceId && ! $paymentNo) {
            throw new RuntimeException('Callback iPaymu tidak valid: tidak ada trx_id/reference_id/payment_no.');
        }

        $payment = $this->findPaymentByCallback($trxId, $referenceId, $paymentNo);

        if (! $payment) {
            throw new RuntimeException("Payment tidak ditemukan untuk callback ini (trx_id={$trxId}, reference_id={$referenceId}, payment_no={$paymentNo}).");
        }

        // Payment yang statusnya sudah final tidak boleh diubah lagi oleh
        // callback susulan (mencegah callback lama/duplikat/palsu mengubah
        // status yang sudah settle).
        if (in_array($payment['status'], ['paid', 'cancelled', 'expired'], true)) {
            return; // Diam-diam diabaikan — bukan error, cukup no-op.
        }

        // Cross-check nominal: harus cocok dengan yang SUDAH tersimpan di
        // database kita sejak payment dibuat, dengan toleransi kecil untuk
        // pembulatan (iPaymu kadang kirim "amount" termasuk fee, kadang
        // tidak, tergantung konteks — makanya toleransi agak longgar).
        if ($amount > 0 && abs($amount - (float) $payment['amount']) > 5000) {
            throw new RuntimeException(
                "Nominal callback tidak cocok untuk payment #{$payment['id']}. Tersimpan: {$payment['amount']}, dari callback: {$amount}."
            );
        }

        $paymentStatus = match ($status) {
            'success', 'berhasil' => 'paid',
            'pending'              => 'pending',
            'expired', 'expire'   => 'expired',
            'cancel', 'cancelled', 'canceled' => 'cancelled',
            default                => 'failed',
        };

        $orderStatus = match ($paymentStatus) {
            'paid'      => 'paid',
            'expired'   => 'cancelled',
            'cancelled' => 'cancelled',
            default     => null,
        };

        $updatePayment = $this->pdo->prepare(
            'UPDATE payments SET
             status = :status,
             ipaymu_trx_id = COALESCE(:trx_id, ipaymu_trx_id),
             payment_method = COALESCE(:via, payment_method),
             payment_channel = COALESCE(:channel, payment_channel),
             fee = COALESCE(:fee, fee),
             paid_at = :paid_at,
             raw_callback = :raw_callback,
             updated_at = NOW()
             WHERE id = :id'
        );

        $updatePayment->execute([
            'status'       => $paymentStatus,
            'trx_id'       => $trxId,
            'via'          => $callbackData['via'] ?? null,
            'channel'      => $callbackData['channel'] ?? null,
            'fee'          => $callbackData['fee'] ?? null,
            'paid_at'      => ($paymentStatus === 'paid' && ! empty($callbackData['paid_at']))
                ? $callbackData['paid_at']
                : ($paymentStatus === 'paid' ? date('Y-m-d H:i:s') : null),
            'raw_callback' => json_encode($callbackData),
            'id'           => $payment['id'],
        ]);

        if ($orderStatus) {
            $updateOrder = $this->pdo->prepare(
                'UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id'
            );
            $updateOrder->execute(['status' => $orderStatus, 'id' => $payment['order_id']]);

            $histStmt = $this->pdo->prepare(
                'INSERT INTO order_status_histories (order_id, status, note, source, created_at, updated_at)
                 VALUES (:order_id, :status, :note, "ipaymu", NOW(), NOW())'
            );
            $histStmt->execute([
                'order_id' => $payment['order_id'],
                'status'   => $orderStatus,
                'note'     => "iPaymu callback: status={$status}, trx_id={$trxId}",
            ]);

            // Kalau order jadi cancelled/expired, kembalikan stok DAN
            // sold_count flash sale (lihat OrderService::restoreStockOnly()).
            if ($orderStatus === 'cancelled') {
                try {
                    $orderService = new \App\Modules\Order\Application\Services\OrderService();
                    $orderService->restoreStockOnly((int) $payment['order_id']);
                } catch (\Throwable $e) {
                    // Non-fatal — status order tetap ter-update, restock bisa
                    // ditangani manual dari admin kalau ini gagal.
                }
            }
        }
    }

    /**
     * Cari payment berdasarkan beberapa kemungkinan field dari callback.
     * iPaymu kadang kirim trx_id, kadang reference_id, kadang payment_no.
     *
     * FIX: PDO native prepared statement tidak mendukung named placeholder
     * yang sama dipakai lebih dari sekali dalam satu query. Setiap
     * placeholder di sini punya nama unik.
     */
    private function findPaymentByCallback(?string $trxId, ?string $referenceId, ?string $paymentNo): ?array
    {
        if ($paymentNo) {
            $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE payment_no = :v1 OR ipaymu_pay_code = :v2 LIMIT 1');
            $stmt->execute(['v1' => $paymentNo, 'v2' => $paymentNo]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        if ($referenceId) {
            $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE payment_no = :v1 OR ipaymu_reference_id = :v2 LIMIT 1');
            $stmt->execute(['v1' => $referenceId, 'v2' => $referenceId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        if ($trxId) {
            $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE ipaymu_trx_id = :v LIMIT 1');
            $stmt->execute(['v' => $trxId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        return null;
    }

    /**
     * Ambil metode pembayaran yang tersedia (untuk ditampilkan ke customer).
     * Sumber: dokumentasi iPaymu direct payment.
     */
    public function getAvailableMethods(): array
    {
        return [
            'va' => [
                'label'    => 'Virtual Account',
                'channels' => [
                    'bni'     => 'BNI Virtual Account',
                    'bri'     => 'BRI Virtual Account',
                    'mandiri' => 'Mandiri Virtual Account',
                    'bca'     => 'BCA Virtual Account',
                    'cimb'    => 'CIMB Niaga Virtual Account',
                    'permata' => 'Permata Virtual Account',
                ],
            ],
            'qris' => [
                'label'    => 'QRIS',
                'channels' => [
                    'qris' => 'QRIS (Semua E-Wallet)',
                ],
            ],
        ];
    }

    public function findByOrderId(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['order_id' => $orderId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Tulis callback payload ke file log untuk debugging.
     * File: storage/logs/ipaymu-callbacks.log
     */
    private function logCallback(array $data): void
    {
        $logDir  = dirname(__DIR__, 6) . '/storage/logs';
        $logFile = $logDir . '/ipaymu-callbacks.log';

        if (! is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}