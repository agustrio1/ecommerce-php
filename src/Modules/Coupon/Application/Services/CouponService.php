<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Application\Services;

use App\Core\Exceptions\ValidationException;
use PDO;

class CouponService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Validasi dan hitung diskon kupon.
     * Return array berisi info kupon + jumlah diskon.
     *
     * CATATAN race condition: validasi usage_limit di sini HANYA untuk
     * feedback awal ke user (supaya bisa dikasih pesan error yang jelas
     * sebelum submit). Pengecekan yang BENAR-BENAR aman dari race condition
     * terjadi di incrementUsage() lewat atomic UPDATE — itu yang jadi
     * sumber kebenaran final soal apakah kupon ini masih boleh dipakai.
     */
    public function apply(string $code, float $subtotal, ?int $userId = null): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1');
        $stmt->execute(['code' => strtoupper(trim($code))]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            throw new ValidationException(['coupon' => 'Kode kupon tidak valid atau tidak aktif.']);
        }

        if ($coupon['starts_at'] && strtotime($coupon['starts_at']) > time()) {
            throw new ValidationException(['coupon' => 'Kupon belum aktif.']);
        }

        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            throw new ValidationException(['coupon' => 'Kupon sudah kadaluarsa.']);
        }

        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            throw new ValidationException(['coupon' => 'Kuota kupon sudah habis.']);
        }

        if ($subtotal < (float) $coupon['min_purchase']) {
            throw new ValidationException([
                'coupon' => 'Minimum pembelian untuk kupon ini adalah Rp ' . number_format($coupon['min_purchase'], 0, ',', '.') . '.',
            ]);
        }

        if ($coupon['user_id'] !== null && $coupon['user_id'] !== $userId) {
            throw new ValidationException(['coupon' => 'Kupon ini tidak berlaku untuk akun Anda.']);
        }

        $discount = 0.0;
        if ($coupon['type'] === 'percentage') {
            $discount = $subtotal * ((float) $coupon['value'] / 100);
            if ($coupon['max_discount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
        } else {
            $discount = min((float) $coupon['value'], $subtotal);
        }

        return [
            'coupon'      => $coupon,
            'code'        => $coupon['code'],
            'discount'    => round($discount, 2),
            'description' => $coupon['description'],
            'type'        => $coupon['type'],
            'value'       => $coupon['value'],
        ];
    }

    /**
     * Tambah counter pemakaian kupon SECARA ATOMIC.
     *
     * PENTING (race condition fix): kondisi "usage_limit belum tercapai"
     * sekarang ikut dicek DI DALAM klausa WHERE update yang sama, bukan
     * dicek terpisah sebelum increment. Ini membuat operasi "cek + tambah"
     * jadi satu langkah atomic di level database — kalau 2 request datang
     * bersamaan saat kuota tinggal 1, cuma SATU yang berhasil UPDATE
     * (rowCount() > 0), yang satu lagi rowCount()-nya 0 dan tahu harus
     * gagal, tanpa mungkin dua-duanya lolos menembus limit.
     *
     * @return bool true kalau berhasil increment, false kalau limit sudah
     *              tercapai (baik karena memang habis, atau kalah race
     *              dengan request lain yang barusan menghabiskan kuota).
     */
    public function incrementUsage(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE coupons
             SET used_count = used_count + 1, updated_at = NOW()
             WHERE code = :code
               AND is_active = 1
               AND (usage_limit IS NULL OR used_count < usage_limit)'
        );
        $stmt->execute(['code' => strtoupper(trim($code))]);

        return $stmt->rowCount() > 0;
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO coupons (code, description, type, value, min_purchase, max_discount, usage_limit, starts_at, expires_at, is_active, created_at, updated_at)
             VALUES (:code, :description, :type, :value, :min_purchase, :max_discount, :usage_limit, :starts_at, :expires_at, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            'code'         => strtoupper(trim($data['code'])),
            'description'  => $data['description'] ?? null,
            'type'         => $data['type'],
            'value'        => $data['value'],
            'min_purchase' => $data['min_purchase'] ?? 0,
            'max_discount' => $data['max_discount'] ?: null,
            'usage_limit'  => $data['usage_limit'] ?: null,
            'starts_at'    => $data['starts_at'] ?: null,
            'expires_at'   => $data['expires_at'] ?: null,
            'is_active'    => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE coupons SET code = :code, description = :description, type = :type, value = :value,
             min_purchase = :min_purchase, max_discount = :max_discount, usage_limit = :usage_limit,
             starts_at = :starts_at, expires_at = :expires_at, is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id'           => $id,
            'code'         => strtoupper(trim($data['code'])),
            'description'  => $data['description'] ?? null,
            'type'         => $data['type'],
            'value'        => $data['value'],
            'min_purchase' => $data['min_purchase'] ?? 0,
            'max_discount' => $data['max_discount'] ?: null,
            'usage_limit'  => $data['usage_limit'] ?: null,
            'starts_at'    => $data['starts_at'] ?: null,
            'expires_at'   => $data['expires_at'] ?: null,
            'is_active'    => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM coupons WHERE id = :id')->execute(['id' => $id]);
    }
}