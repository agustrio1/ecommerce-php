<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Mail\Mailer;

/**
 * Dijalankan sebagai proses background terpisah oleh AuthService supaya
 * request register() tidak perlu menunggu SMTP selesai.
 *
 * Usage: php cli mail:send-verification {userId} {token}
 */
class MailSendVerificationCommand
{
    public function handle(array $args): int
    {
        $userId = (int) ($args[0] ?? 0);
        $token  = (string) ($args[1] ?? '');

        if (! $userId || $token === '') {
            echo "Usage: php cli mail:send-verification {userId} {token}\n";
            return 1;
        }

        $pdo  = db();
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (! $user) {
            echo "User {$userId} tidak ditemukan, email verifikasi dibatalkan.\n";
            return 1;
        }

        $verifyUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/verify-email?token=' . $token;
        $html      = $this->template($user['name'], $verifyUrl);

        try {
            (new Mailer())->send(
                $user['email'],
                'Verifikasi Email Anda - ' . env('APP_NAME', 'Ecommerce'),
                $html,
                $user['name']
            );

            echo "Email verifikasi terkirim ke {$user['email']}\n";
            return 0;
        } catch (\Throwable $e) {
            echo "Gagal kirim email verifikasi: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function template(string $name, string $url): string
    {
        $appName = env('APP_NAME', 'Ecommerce');

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2>Halo, {$name}!</h2>
            <p>Terima kasih sudah mendaftar di {$appName}. Klik tombol di bawah untuk verifikasi email Anda:</p>
            <p style="margin: 24px 0;">
                <a href="{$url}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;">
                    Verifikasi Email
                </a>
            </p>
            <p>Atau salin link berikut ke browser Anda:</p>
            <p style="color:#666;word-break:break-all;">{$url}</p>
            <p>Link ini berlaku selama 60 menit.</p>
        </div>
        HTML;
    }
}