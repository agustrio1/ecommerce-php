<?php

declare(strict_types=1);

namespace App\Modules\Setting\Application\Services;

use App\Modules\Setting\Domain\Repositories\SettingRepositoryInterface;
use App\Modules\Setting\Infrastructure\Persistence\MysqlSettingRepository;

/**
 * SettingService
 *
 * Central service untuk baca/tulis semua setting aplikasi.
 * Di-cache per-request supaya tidak query DB berulang kali.
 *
 * Setting dikelompokkan dalam group:
 *   - general     : nama toko, deskripsi, logo, dll
 *   - store       : alamat toko (untuk Biteship origin), kontak
 *   - ipaymu      : VA, API key, mode (sandbox/production)
 *   - biteship    : API key, origin area_id, origin location_id
 *   - seo         : meta title, meta description, keywords, JSON-LD
 *   - social      : link media sosial
 *   - appearance  : warna, font, header/footer custom
 */
class SettingService
{
    private static ?SettingService $instance = null;
    private SettingRepositoryInterface $repository;

    private function __construct()
    {
        $this->repository = new MysqlSettingRepository();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        $this->repository->set($key, $value, $group);
    }

    public function setMany(array $data, string $group = 'general'): void
    {
        $this->repository->setMany($data, $group);
    }

    public function getGroup(string $group): array
    {
        return $this->repository->getByGroup($group);
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    // ===================== SHORTCUT PER GROUP =====================

    public function general(): array
    {
        return $this->getGroup('general');
    }

    public function store(): array
    {
        return $this->getGroup('store');
    }

    public function ipaymu(): array
    {
        return $this->getGroup('ipaymu');
    }

    public function biteship(): array
    {
        return $this->getGroup('biteship');
    }

    public function seo(): array
    {
        return $this->getGroup('seo');
    }

    // ===================== GETTER SPESIFIK =====================

    // iPaymu
    public function ipaymuVa(): string
    {
        return (string) $this->get('ipaymu_va', '');
    }

    public function ipaymuApiKey(): string
    {
        return (string) $this->get('ipaymu_api_key', '');
    }

    public function ipaymuMode(): string
    {
        return (string) $this->get('ipaymu_mode', 'sandbox');
    }

    public function ipaymuBaseUrl(): string
    {
        return $this->ipaymuMode() === 'production'
            ? 'https://my.ipaymu.com/api/v2'
            : 'https://sandbox.ipaymu.com/api/v2';
    }

    // Biteship
    public function biteshipApiKey(): string
    {
        return (string) $this->get('biteship_api_key', env('BITESHIP_API_KEY', ''));
    }

    public function biteshipOriginAreaId(): string
    {
        return (string) $this->get('biteship_origin_area_id', env('BITESHIP_ORIGIN_AREA_ID', ''));
    }

    public function biteshipOriginPostalCode(): string
    {
        return (string) $this->get('store_postal_code', '');
    }

    // Store info (dipakai sebagai shipper/origin di Biteship)
    public function storeName(): string
    {
        return (string) $this->get('store_name', config('app.name', 'Toko'));
    }

    public function storePhone(): string
    {
        return (string) $this->get('store_phone', '');
    }

    public function storeEmail(): string
    {
        return (string) $this->get('store_email', '');
    }

    public function storeAddress(): string
    {
        return (string) $this->get('store_address', '');
    }

    public function storeCity(): string
    {
        return (string) $this->get('store_city', '');
    }

    public function storeProvince(): string
    {
        return (string) $this->get('store_province', '');
    }

    public function storePostalCode(): string
    {
        return (string) $this->get('store_postal_code', '');
    }
    
    // Biteship — tambahan
public function biteshipBaseUrl(): string
{
    // Base URL Biteship cuma satu, mode test/live dari prefix API key-nya
    return 'https://api.biteship.com';
}

public function biteshipOriginLocationId(): string
{
    return (string) $this->get('biteship_origin_location_id', '');
}

public function biteshipCouriers(): string
{
    return (string) $this->get('biteship_couriers', 'jne,sicepat,anteraja,jnt,tiki');
}
}