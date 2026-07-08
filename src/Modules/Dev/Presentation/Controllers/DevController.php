<?php

declare(strict_types=1);

namespace App\Modules\Dev\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Payment\Application\Services\PaymentService;
use PDO;

/**
 * DevController
 *
 * Controller ini HANYA aktif ketika APP_ENV !== 'production'.
 * Digunakan untuk mensimulasikan callback/webhook dari iPaymu tanpa
 * perlu expose localhost ke internet (misal pakai ngrok).
 *
 * Cara kerja:
 * - Halaman simulator menampilkan daftar payment yang statusnya masih "pending"
 * - Admin bisa klik tombol "Simulasi Bayar", "Simulasi Gagal", atau "Simulasi Cancel"
 * - Sistem langsung memanggil PaymentService::handleCallback() seolah-olah
 *   iPaymu yang mengirimnya
 */
class DevController
{
    private PaymentService $paymentService;
    private PDO $pdo;

    public function __construct()
    {
        // Guard: pastikan tidak bisa diakses di production
        if (env('APP_ENV', 'production') === 'production') {
            abort(404);
        }

        $this->paymentService = new PaymentService();
        $this->pdo            = db();
    }

    /**
     * Halaman simulator — list semua payment pending beserta tombol simulasi.
     */
    public function paymentSimulator(Request $request): Response
    {
        $stmt = $this->pdo->query(
            'SELECT p.*, o.order_number, o.total AS order_total, o.status AS order_status
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             ORDER BY p.created_at DESC
             LIMIT 50'
        );

        $payments = $stmt->fetchAll();

        return Response::make(view('dev.payment-simulator', [
            'title'    => '[DEV] Payment Simulator',
            'payments' => $payments,
        ]));
    }

    /**
     * Endpoint yang menerima aksi simulasi dari halaman simulator.
     * Membangun payload callback persis seperti yang iPaymu kirimkan,
     * lalu meneruskan ke PaymentService::handleCallback().
     *
     * Status iPaymu:
     *   1 = sukses/paid
     *   2 = pending
     *   3 = cancel/gagal
     */
    public function simulateCallback(Request $request): Response
    {
        $paymentNo = $request->input('payment_no');
        $status    = $request->input('status', '1'); // default: sukses

        if (! $paymentNo) {
            return Response::json(['success' => false, 'message' => 'payment_no wajib diisi.'], 422);
        }

        // Ambil data payment dari DB supaya bisa isi payload yang realistis
        $stmt = $this->pdo->prepare(
            'SELECT p.*, o.order_number FROM payments p
             JOIN orders o ON o.id = p.order_id
             WHERE p.payment_no = :payment_no LIMIT 1'
        );
        $stmt->execute(['payment_no' => $paymentNo]);
        $payment = $stmt->fetch();

        if (! $payment) {
            return Response::json(['success' => false, 'message' => "Payment [{$paymentNo}] tidak ditemukan."], 404);
        }

        // Buat payload yang meniru format callback resmi iPaymu
        // Referensi: https://documenter.getpostman.com/view/40296808/2sB3WtseBT
        $simulatedPayload = [
            'trx_id'          => $payment['ipaymu_trx_id'] ?? 'SIM-TRX-' . strtoupper(substr(uniqid(), -8)),
            'reference_id'    => $paymentNo,
            'status'          => $status,
            'status_code'     => $status,
            'via'             => $payment['ipaymu_via'] ?? 'va',
            'channel'         => $payment['ipaymu_channel'] ?? 'bni',
            'payment_no'      => $payment['ipaymu_pay_code'] ?? null,
            'payment_method'  => $payment['payment_method'] ?? 'va',
            'payment_channel' => $payment['payment_channel'] ?? 'bni',
            'amount'          => $payment['amount'],
            'fee'             => 0,
            'note'            => '[DEV] Simulated callback - status: ' . match ($status) {
                '1' => 'SUCCESS',
                '2' => 'PENDING',
                '3' => 'CANCEL',
                default => 'UNKNOWN',
            },
            'sender_name'     => 'SIMULATOR',
            'receiver_bank_account_name' => 'TOKO',
            'created'         => date('Y-m-d H:i:s'),
            'expired'         => $payment['expired_at'] ?? null,
            '_simulated'      => true,  // marker bahwa ini simulasi, bukan dari iPaymu asli
        ];

        try {
            $this->paymentService->handleCallback($simulatedPayload);

            $statusLabel = match ($status) {
                '1' => 'PAID (sukses)',
                '2' => 'PENDING',
                '3' => 'CANCELLED',
                default => 'UNKNOWN',
            };

            return Response::json([
                'success' => true,
                'message' => "Callback disimulasikan dengan status: {$statusLabel}",
                'payload' => $simulatedPayload,
            ]);
        } catch (\Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
                'payload' => $simulatedPayload,
            ], 500);
        }
    }
}
