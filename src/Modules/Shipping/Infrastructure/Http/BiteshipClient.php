<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Infrastructure\Http;

use App\Modules\Setting\Application\Services\SettingService;
use RuntimeException;

class BiteshipClient
{
    private const BASE_URL = 'https://api.biteship.com';

    private string $apiKey;
    private SettingService $setting;

    public function __construct()
    {
        $this->setting = SettingService::getInstance();
        $this->apiKey  = $this->setting->biteshipApiKey();
    }

    public function searchArea(string $input): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Biteship API key belum dikonfigurasi. Isi dulu di Pengaturan → Shipping.');
        }

        $response = $this->request('GET', '/v1/maps/areas', null, [
            'countries' => 'ID',
            'input'     => $input,
            'type'      => 'single',
        ]);

        return $response['areas'] ?? [];
    }

    public function getRates(
        string $originPostalCode,
        string $destinationPostalCode,
        array  $items,
        string $couriers = ''
    ): array {
        if ($couriers === '') {
            $couriers = $this->setting->biteshipCouriers();
        }

        $response = $this->request('POST', '/v1/rates/couriers', [
            'origin_postal_code'      => (int) $originPostalCode,
            'destination_postal_code' => (int) $destinationPostalCode,
            'couriers'                => $couriers,
            'items'                   => $items,
        ]);

        return $response['pricing'] ?? [];
    }

    public function getRatesByAreaId(
        string $originAreaId,
        string $destinationAreaId,
        array  $items,
        string $couriers = ''
    ): array {
        if ($couriers === '') {
            $couriers = $this->setting->biteshipCouriers();
        }

        $response = $this->request('POST', '/v1/rates/couriers', [
            'origin_area_id'      => $originAreaId,
            'destination_area_id' => $destinationAreaId,
            'couriers'            => $couriers,
            'items'               => $items,
        ]);

        return $response['pricing'] ?? [];
    }

    public function getRatesByCoordinate(
        float  $originLat,
        float  $originLng,
        float  $destinationLat,
        float  $destinationLng,
        array  $items,
        string $couriers = ''
    ): array {
        if ($couriers === '') {
            $couriers = $this->setting->biteshipCouriers();
        }

        $response = $this->request('POST', '/v1/rates/couriers', [
            'origin_latitude'       => $originLat,
            'origin_longitude'      => $originLng,
            'destination_latitude'  => $destinationLat,
            'destination_longitude' => $destinationLng,
            'couriers'              => $couriers,
            'items'                 => $items,
        ]);

        return $response['pricing'] ?? [];
    }

    public function createOrder(array $params): array
    {
        $params = array_merge([
            'shipper_contact_name'  => $this->setting->storeName(),
            'shipper_contact_phone' => $this->setting->storePhone(),
            'shipper_contact_email' => $this->setting->storeEmail(),
        ], $params);

        if (! isset($params['delivery_type'])) {
            $params['delivery_type'] = 'now';
        }

        return $this->request('POST', '/v1/orders', $params);
    }

    public function getOrder(string $orderId): array
    {
        return $this->request('GET', '/v1/orders/' . $orderId);
    }

    public function deleteOrder(string $orderId): array
    {
        return $this->request('DELETE', '/v1/orders/' . $orderId);
    }

    public function getTracking(string $trackingId): array
    {
        return $this->request('GET', '/v1/trackings/' . $trackingId);
    }

    public function getPublicTracking(string $waybillId, string $courierCode): array
    {
        return $this->request('GET', '/v1/trackings/waybills/' . $waybillId, null, [
            'courier_code' => $courierCode,
        ]);
    }

    public function getCouriers(): array
    {
        $response = $this->request('GET', '/v1/couriers');

        return $response['couriers'] ?? [];
    }

    public function getLocations(): array
    {
        return $this->request('GET', '/v1/locations');
    }

    /**
     * HTTP request ke Biteship API.
     *
     * PERUBAHAN PERFORMA:
     * - CONNECTTIMEOUT dipisah dari TIMEOUT total. Kalau server Biteship
     *   lambat merespons handshake awal (koneksi jelek/DNS lambat), request
     *   gagal cepat (5 detik) daripada nunggu sampai batas 30 detik lama.
     * - TIMEOUT total diturunkan dari 30 ke 15 detik — API rates biasanya
     *   merespons dalam <3 detik; 15 detik sudah generous untuk kondisi
     *   jaringan yang kurang stabil tanpa bikin user nunggu lama kalau
     *   memang API-nya down/sangat lambat.
     * - CURLOPT_ENCODING '' mengaktifkan gzip/deflate otomatis kalau
     *   server support, mengurangi waktu transfer response.
     * - CURLOPT_TCP_NODELAY mengurangi latency kecil dari Nagle's algorithm
     *   pada koneksi baru.
     */
    private function request(string $method, string $endpoint, ?array $body = null, array $queryParams = []): array
    {
        $url = self::BASE_URL . $endpoint;

        if (! empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $headers = [
            'accept: application/json',
            'content-type: application/json',
            'authorization: ' . $this->apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TCP_NODELAY, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
                break;
        }

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($err) {
            throw new RuntimeException('Biteship cURL error: ' . $err);
        }

        if ($response === false || $response === '') {
            throw new RuntimeException('Biteship empty response (HTTP ' . $httpCode . ')');
        }

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new RuntimeException('Biteship response bukan JSON valid: ' . $response);
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $errorMsg  = $decoded['error'] ?? $decoded['message'] ?? 'Unknown error';
            $errorCode = $decoded['code'] ?? $httpCode;

            throw new RuntimeException("Biteship error [{$errorCode}]: {$errorMsg}");
        }

        return $decoded;
    }
}