<?php

declare(strict_types=1);

namespace App\Core\Support;

use PDO;

/**
 * RateLimiter
 *
 * Database-based rate limiter. Mencatat jumlah percobaan (attempts) per key
 * (mis. "login:192.168.1.1" atau "login:email@user.com") dalam window waktu tertentu.
 *
 * Pemakaian:
 *   $limiter = new RateLimiter();
 *
 *   if ($limiter->tooManyAttempts('login:' . $ip, maxAttempts: 5, decayMinutes: 1)) {
 *       // tolak request, kasih tau coba lagi nanti
 *   }
 *
 *   $limiter->hit('login:' . $ip, decayMinutes: 1);   // catat 1 percobaan
 *   $limiter->clear('login:' . $ip);                  // reset setelah berhasil login
 */
class RateLimiter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * Cek apakah key tertentu sudah melebihi batas maksimal percobaan.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $row = $this->getRow($key);

        if ($row === null) {
            return false;
        }

        // Window sudah lewat, otomatis dianggap belum ada percobaan baru.
        if (strtotime($row['reset_at']) <= time()) {
            return false;
        }

        return (int) $row['attempts'] >= $maxAttempts;
    }

    /**
     * Catat satu percobaan baru untuk key tertentu.
     * Jika window sebelumnya sudah habis, counter di-reset ke 1 dengan window baru.
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $row = $this->getRow($key);
        $now = time();

        if ($row === null || strtotime($row['reset_at']) <= $now) {
            $resetAt = date('Y-m-d H:i:s', $now + ($decayMinutes * 60));

            $stmt = $this->pdo->prepare(
                "INSERT INTO rate_limits (`key`, attempts, reset_at, created_at, updated_at)
                 VALUES (:key, 1, :reset_at, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE attempts = 1, reset_at = :reset_at2, updated_at = NOW()"
            );
            $stmt->execute(['key' => $key, 'reset_at' => $resetAt, 'reset_at2' => $resetAt]);

            return 1;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE `key` = :key"
        );
        $stmt->execute(['key' => $key]);

        return (int) $row['attempts'] + 1;
    }

    /**
     * Hapus catatan percobaan untuk key tertentu (panggil setelah aksi berhasil,
     * contoh: setelah login sukses, reset counter percobaan login yang gagal).
     */
    public function clear(string $key): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
    }

    /**
     * Sisa detik sebelum key tertentu boleh mencoba lagi (0 jika sudah boleh).
     */
    public function availableInSeconds(string $key): int
    {
        $row = $this->getRow($key);

        if ($row === null) {
            return 0;
        }

        $remaining = strtotime($row['reset_at']) - time();

        return max(0, $remaining);
    }

    private function getRow(string $key): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rate_limits WHERE `key` = :key LIMIT 1");
        $stmt->execute(['key' => $key]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}