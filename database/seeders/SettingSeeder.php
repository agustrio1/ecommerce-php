<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database\Seeder;
use App\Modules\Setting\Application\Services\SettingService;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = SettingService::getInstance();

        // General
        $setting->setMany([
            'app_name'          => 'Ecommerce PHP',
            'app_tagline'       => 'Toko Online Terpercaya',
            'app_logo'          => null,
            'app_favicon'       => null,
            'app_currency'      => 'IDR',
            'app_currency_symbol' => 'Rp',
            'app_locale'        => 'id',
            'app_timezone'      => 'Asia/Jakarta',
        ], 'general');

        // Store — info toko untuk Biteship origin
        $setting->setMany([
            'store_name'        => 'Nama Toko Anda',
            'store_email'       => '',
            'store_phone'       => '',
            'store_address'     => '',
            'store_district'    => '',
            'store_city'        => '',
            'store_province'    => '',
            'store_postal_code' => '',
            'store_area_id'     => '', // Biteship area ID untuk origin
            'store_latitude'    => '',
            'store_longitude'   => '',
        ], 'store');

        // iPaymu
        $setting->setMany([
            'ipaymu_mode'       => 'sandbox',
            'ipaymu_va'         => '',
            'ipaymu_api_key'    => '',
            'ipaymu_notify_url' => '',
            'ipaymu_return_url' => '',
            'ipaymu_cancel_url' => '',
        ], 'ipaymu');

        // Biteship
        $setting->setMany([
            'biteship_api_key'            => '',
            'biteship_origin_area_id'     => '',
            'biteship_origin_location_id' => '',
            'biteship_couriers'           => 'jne,sicepat,anteraja,jnt,tiki',
        ], 'biteship');

        // SEO
        $setting->setMany([
            'seo_title'             => 'Ecommerce PHP — Toko Online Terpercaya',
            'seo_description'       => 'Belanja produk berkualitas dengan harga terjangkau',
            'seo_keywords'          => '',
            'seo_og_image'          => null,
            'seo_robots'            => 'index, follow',
            'seo_google_analytics'  => '',
            'seo_google_site_verification' => '',
            'seo_jsonld_organization' => json_encode([
                '@context'  => 'https://schema.org',
                '@type'     => 'Organization',
                'name'      => 'Ecommerce PHP',
                'url'       => '',
                'logo'      => '',
                'contactPoint' => [
                    '@type'       => 'ContactPoint',
                    'telephone'   => '',
                    'contactType' => 'customer service',
                ],
            ]),
        ], 'seo');

        // Social Media
        $setting->setMany([
            'social_instagram'  => '',
            'social_facebook'   => '',
            'social_tiktok'     => '',
            'social_twitter'    => '',
            'social_youtube'    => '',
            'social_whatsapp'   => '',
            'social_shopee'     => '',
            'social_tokopedia'  => '',
        ], 'social');

        // Appearance
        $setting->setMany([
            'appearance_primary_color'  => '#f97316',
            'appearance_header_script'  => '',
            'appearance_footer_script'  => '',
        ], 'appearance');

        echo "  - Settings default ter-seed.\n";
    }
}