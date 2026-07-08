<?php

declare(strict_types=1);

namespace App\Modules\Setting\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Setting\Application\Services\SettingService;

class SettingController
{
    private SettingService $settingService;

    public function __construct()
    {
        $this->settingService = SettingService::getInstance();
    }

    public function general(Request $request): Response
    {
        return Response::make(view('Setting::general', [
            'settings' => $this->settingService->getGroup('general'),
            'social'   => $this->settingService->getGroup('social'),
        ]));
    }

    public function saveGeneral(Request $request): Response
    {
        $this->settingService->setMany([
            'app_name'    => $request->input('app_name'),
            'app_tagline' => $request->input('app_tagline'),
        ], 'general');

        $this->settingService->setMany([
            'social_instagram' => $request->input('social_instagram'),
            'social_facebook'  => $request->input('social_facebook'),
            'social_tiktok'    => $request->input('social_tiktok'),
            'social_twitter'   => $request->input('social_twitter'),
            'social_youtube'   => $request->input('social_youtube'),
            'social_whatsapp'  => $request->input('social_whatsapp'),
            'social_shopee'    => $request->input('social_shopee'),
            'social_tokopedia' => $request->input('social_tokopedia'),
        ], 'social');

        Session::flash('success', 'Pengaturan umum berhasil disimpan.');

        return Response::redirect('/admin/settings/general');
    }

    public function store(Request $request): Response
    {
        return Response::make(view('Setting::store', [
            'settings' => $this->settingService->getGroup('store'),
        ]));
    }

    public function saveStore(Request $request): Response
    {
        $this->settingService->setMany([
            'store_name'        => $request->input('store_name'),
            'store_email'       => $request->input('store_email'),
            'store_phone'       => $request->input('store_phone'),
            'store_address'     => $request->input('store_address'),
            'store_district'    => $request->input('store_district'),
            'store_city'        => $request->input('store_city'),
            'store_province'    => $request->input('store_province'),
            'store_postal_code' => $request->input('store_postal_code'),
            'store_area_id'     => $request->input('store_area_id'),
            'store_area_name'   => $request->input('store_area_name'),
            'store_latitude'    => $request->input('store_latitude'),
            'store_longitude'   => $request->input('store_longitude'),
        ], 'store');

        Session::flash('success', 'Informasi toko berhasil disimpan.');

        return Response::redirect('/admin/settings/store');
    }

    public function payment(Request $request): Response
    {
        return Response::make(view('Setting::payment', [
            'ipaymu' => $this->settingService->getGroup('ipaymu'),
        ]));
    }

    public function savePayment(Request $request): Response
    {
        $this->settingService->setMany([
            'ipaymu_mode'       => $request->input('ipaymu_mode', 'sandbox'),
            'ipaymu_va'         => $request->input('ipaymu_va'),
            'ipaymu_api_key'    => $request->input('ipaymu_api_key'),
            'ipaymu_notify_url' => rtrim(env('APP_URL', ''), '/') . '/webhooks/ipaymu',
            'ipaymu_return_url' => rtrim(env('APP_URL', ''), '/') . '/orders/callback/ipaymu',
            'ipaymu_cancel_url' => rtrim(env('APP_URL', ''), '/') . '/checkout',
        ], 'ipaymu');

        Session::flash('success', 'Pengaturan iPaymu berhasil disimpan.');

        return Response::redirect('/admin/settings/payment');
    }

    public function shipping(Request $request): Response
    {
        return Response::make(view('Setting::shipping', [
            'biteship' => $this->settingService->getGroup('biteship'),
        ]));
    }

    public function saveShipping(Request $request): Response
    {
        $this->settingService->setMany([
            'biteship_api_key'            => $request->input('biteship_api_key'),
            'biteship_origin_area_id'     => $request->input('biteship_origin_area_id'),
            'biteship_origin_location_id' => $request->input('biteship_origin_location_id'),
            'biteship_couriers'           => $request->input('biteship_couriers', 'jne,sicepat,anteraja,jnt,tiki'),
        ], 'biteship');

        Session::flash('success', 'Pengaturan Biteship berhasil disimpan.');

        return Response::redirect('/admin/settings/shipping');
    }

    public function seo(Request $request): Response
    {
        return Response::make(view('Setting::seo', [
            'seo' => $this->settingService->getGroup('seo'),
        ]));
    }

    public function saveSeo(Request $request): Response
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $this->settingService->storeName(),
            'url'      => env('APP_URL', ''),
            'logo'     => $request->input('seo_og_image', ''),
            'contactPoint' => [
                '@type'             => 'ContactPoint',
                'telephone'         => $this->settingService->storePhone(),
                'contactType'       => 'customer service',
                'areaServed'        => 'ID',
                'availableLanguage' => 'Indonesian',
            ],
            'sameAs' => array_filter([
                $this->settingService->get('social_instagram'),
                $this->settingService->get('social_facebook'),
                $this->settingService->get('social_tiktok'),
                $this->settingService->get('social_twitter'),
            ]),
        ];

        $this->settingService->setMany([
            'seo_title'                    => $request->input('seo_title'),
            'seo_description'              => $request->input('seo_description'),
            'seo_keywords'                 => $request->input('seo_keywords'),
            'seo_robots'                   => $request->input('seo_robots', 'index, follow'),
            'seo_google_analytics'         => $request->input('seo_google_analytics'),
            'seo_google_site_verification' => $request->input('seo_google_site_verification'),
            'seo_og_image'                 => $request->input('seo_og_image'),
            'seo_jsonld_organization'      => json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ], 'seo');

        Session::flash('success', 'Pengaturan SEO berhasil disimpan. JSON-LD otomatis di-generate.');

        return Response::redirect('/admin/settings/seo');
    }

    /**
     * AJAX: Search area Biteship untuk autocomplete input alamat.
     * Dipakai oleh DUA route: /admin/settings/search-area (perlu login
     * admin) DAN /api/search-area (PUBLIC, dipakai checkout storefront).
     *
     * PENTING (keamanan): karena salah satu route yang memakai method ini
     * bersifat PUBLIC (tanpa auth), detail exception (nama class, file,
     * baris kode) TIDAK BOLEH dikirim ke response. Sebelumnya kode debug
     * sementara membocorkan path lengkap server ke siapa pun yang memicu
     * error di endpoint ini — informasi itu bisa dipakai penyerang untuk
     * memetakan struktur server. Sekarang detail teknis dicatat ke error
     * log server saja (error_log), response ke client cukup pesan generik.
     */
    public function searchArea(Request $request): Response
    {
        header('Content-Type: application/json');

        $input = trim((string) $request->query('input', ''));

        if (strlen($input) < 3) {
            echo json_encode(['success' => false, 'areas' => []]);
            exit;
        }

        try {
            $apiKey = $this->settingService->biteshipApiKey();
            if (empty($apiKey)) {
                $apiKey = env('BITESHIP_API_KEY', '');
            }

            if (empty($apiKey)) {
                error_log('searchArea: Biteship API key kosong.');
                echo json_encode(['success' => false, 'areas' => []]);
                exit;
            }

            $url = 'https://api.biteship.com/v1/maps/areas?' . http_build_query([
                'countries' => 'ID',
                'input'     => $input,
                'type'      => 'single',
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'content-type: application/json',
                'authorization: ' . $apiKey,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                error_log('searchArea cURL error: ' . $curlErr);
                echo json_encode(['success' => false, 'areas' => []]);
                exit;
            }

            $decoded = json_decode($response, true);
            if ($decoded === null) {
                error_log('searchArea: response Biteship bukan JSON valid.');
                echo json_encode(['success' => false, 'areas' => []]);
                exit;
            }

            echo json_encode(['success' => true, 'areas' => $decoded['areas'] ?? []]);
            exit;

        } catch (\Throwable $e) {
            // Detail lengkap HANYA ke log server, tidak pernah ke response.
            error_log('searchArea error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode(['success' => false, 'areas' => []]);
            exit;
        }
    }
}