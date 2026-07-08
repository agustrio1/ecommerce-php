<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Http;

use App\Modules\Setting\Application\Services\SettingService;
use RuntimeException;

/**
 * IpaymuClient
 *
 * HTTP client untuk iPaymu Payment Gateway v2 API.
 *
 * Signature algorithm (dari dokumentasi resmi iPaymu):
 *   1. JSON encode body dengan JSON_UNESCAPED_SLASHES
 *   2. SHA256 hash body lalu lowercase hasilnya
 *   3. String to sign: METHOD:VA:hash_body:APIKEY
 *   4. HMAC-SHA256 dengan key = APIKEY
 *   5. Header: va, signature, timestamp (YmdHis)
 */
class IpaymuClient
{
    private string $va;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $setting       = SettingService::getInstance();
        $this->va      = $setting->ipaymuVa();
        $this->apiKey  = $setting->ipaymuApiKey();
        $this->baseUrl = $setting->ipaymuBaseUrl();
    }

    /**
     * Redirect Payment — user akan diarahkan ke halaman pembayaran iPaymu.
     *
     * @param array{
     *   product: string[],
     *   qty: string[],
     *   price: string[],
     *   returnUrl: string,
     *   cancelUrl: string,
     *   notifyUrl: string,
     *   referenceId: string,
     *   buyerName?: string,
     *   buyerPhone?: string,
     *   buyerEmail?: string,
     *   amount?: int
     * } $params
     * @return array{SessionID: string, Url: string}
     */
    public function redirectPayment(array $params): array
    {
        $response = $this->request('POST', '/payment', $params);

        if (! isset($response['Data']['SessionID'], $response['Data']['Url'])) {
            throw new RuntimeException('iPaymu redirect payment response tidak valid: ' . json_encode($response));
        }

        return $response['Data'];
    }

    /**
     * Direct Payment — bayar langsung via metode tertentu (VA bank, dll).
     *
     * @param array{
     *   name: string,
     *   phone: string,
     *   email: string,
     *   amount: int,
     *   notifyUrl: string,
     *   referenceId: string,
     *   paymentMethod: string,
     *   paymentChannel: string,
     *   expired?: int
     * } $params
     */
    public function directPayment(array $params): array
    {
        $response = $this->request('POST', '/payment/direct', $params);

        if (! isset($response['Data'])) {
            throw new RuntimeException('iPaymu direct payment response tidak valid: ' . json_encode($response));
        }

        return $response['Data'];
    }

    /**
     * Cek status transaksi berdasarkan ID transaksi iPaymu.
     */
    public function checkTransaction(string $transactionId): array
    {
        $response = $this->request('POST', '/payment/status', [
            'transactionId' => $transactionId,
        ]);

        return $response['Data'] ?? [];
    }

    /**
     * Kirim HTTP request ke iPaymu API dengan signature yang benar.
     */
    private function request(string $method, string $endpoint, array $body): array
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        // Generate signature sesuai dokumentasi resmi iPaymu
        $jsonBody    = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = strtoupper($method) . ':' . $this->va . ':' . $requestBody . ':' . $this->apiKey;
        $signature   = hash_hmac('sha256', $stringToSign, $this->apiKey);
        $timestamp   = date('YmdHis');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'va: ' . $this->va,
            'signature: ' . $signature,
            'timestamp: ' . $timestamp,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $err      = curl_error($ch);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new RuntimeException('iPaymu cURL error: ' . $err);
        }

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new RuntimeException('iPaymu response bukan JSON valid: ' . $response);
        }

        if (isset($decoded['Status']) && $decoded['Status'] !== 200) {
            throw new RuntimeException('iPaymu error ' . $decoded['Status'] . ': ' . ($decoded['Message'] ?? 'Unknown error'));
        }

        return $decoded;
    }
}