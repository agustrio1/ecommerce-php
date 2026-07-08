<?php

declare(strict_types=1);

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use RuntimeException;

/**
 * Mailer
 *
 * Wrapper pengiriman email dengan 3 driver:
 *   - 'log'    : tidak benar-benar mengirim, hanya ditulis ke
 *                storage/logs/mail.log — untuk development.
 *   - 'smtp'   : kirim via PHPMailer + SMTP (butuh port 587/465 terbuka
 *                di server — sering diblokir shared hosting).
 *   - 'resend' : kirim via Resend HTTP API (https://resend.com) — cuma
 *                butuh port 443 (HTTPS) yang hampir selalu terbuka di
 *                semua hosting, jadi lebih andal dibanding SMTP di
 *                shared hosting yang suka blokir port SMTP keluar.
 *
 * Driver ditentukan dari config('mail')['driver'].
 *
 * Pemakaian (sama persis untuk semua driver, tidak perlu ubah kode caller):
 *   (new Mailer())->send(
 *       to: 'user@example.com',
 *       subject: 'Verifikasi Email Anda',
 *       html: '<p>Klik link berikut...</p>'
 *   );
 */
class Mailer
{
    private array $config;

    public function __construct()
    {
        $this->config = config('mail', []);
    }

    public function send(string $to, string $subject, string $html, ?string $toName = null): bool
    {
        $driver = $this->config['driver'] ?? 'smtp';

        return match ($driver) {
            'log'    => $this->logOnly($to, $subject, $html),
            'resend' => $this->sendViaResend($to, $subject, $html, $toName),
            default  => $this->sendViaSmtp($to, $subject, $html, $toName),
        };
    }

    /**
     * Kirim via Resend HTTP API. Hanya butuh port 443 (HTTPS) — jauh lebih
     * kompatibel dengan shared hosting dibanding SMTP yang sering diblokir
     * di port 587/465.
     *
     * PENTING: alamat pengirim (config['resend']['from_address']) harus
     * dari domain yang SUDAH DIVERIFIKASI di dashboard Resend. Kalau belum
     * diverifikasi, Resend hanya mengizinkan kirim ke alamat email yang
     * dipakai mendaftar akun Resend itu sendiri (mode sandbox/testing).
     */
    private function sendViaResend(string $to, string $subject, string $html, ?string $toName = null): bool
    {
        $apiKey      = $this->config['resend']['api_key'] ?? '';
        $fromAddress = $this->config['resend']['from_address'] ?? ($this->config['from']['address'] ?? '');
        $fromName    = $this->config['resend']['from_name'] ?? ($this->config['from']['name'] ?? '');

        if ($apiKey === '') {
            $this->logError($to, $subject, 'Resend API key belum dikonfigurasi.');
            throw new RuntimeException('Resend API key belum dikonfigurasi. Isi RESEND_API_KEY di .env.');
        }

        if ($fromAddress === '') {
            $this->logError($to, $subject, 'Resend from_address belum dikonfigurasi.');
            throw new RuntimeException('Alamat pengirim Resend belum dikonfigurasi.');
        }

        $from = $fromName !== '' ? "{$fromName} <{$fromAddress}>" : $fromAddress;
        $toFormatted = $toName ? "{$toName} <{$to}>" : $to;

        $payload = [
            'from'    => $from,
            'to'      => [$toFormatted],
            'subject' => $subject,
            'html'    => $html,
            'text'    => strip_tags($html),
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            $this->logError($to, $subject, 'Resend cURL error: ' . $err);
            throw new RuntimeException("Gagal mengirim email ke {$to} via Resend: {$err}");
        }

        $decoded = json_decode((string) $response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($decoded['id'])) {
            return true;
        }

        // Format error resmi Resend: {"statusCode": ..., "name": "...", "message": "..."}
        // 'name' berguna untuk debugging cepat (mis. "restricted_api_key",
        // "validation_error", "missing_api_key") tanpa perlu baca full response.
        $errorName = $decoded['name'] ?? 'unknown_error';
        $errorMsg  = $decoded['message'] ?? ('HTTP ' . $httpCode . ': ' . $response);
        $this->logError($to, $subject, "Resend API error [{$errorName}]: {$errorMsg}");

        throw new RuntimeException("Gagal mengirim email ke {$to} via Resend: {$errorMsg}");
    }

    /**
     * Kirim via PHPMailer + SMTP (driver lama, dipertahankan sebagai
     * alternatif kalau Resend tidak dipakai).
     */
    private function sendViaSmtp(string $to, string $subject, string $html, ?string $toName = null): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->config['from']['address'], $this->config['from']['name']);
            $mail->addAddress($to, $toName ?? '');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();

            return true;
        } catch (PHPMailerException $e) {
            $this->logError($to, $subject, $mail->ErrorInfo);

            throw new RuntimeException("Gagal mengirim email ke {$to}: " . $mail->ErrorInfo, 0, $e);
        }
    }

    private function logOnly(string $to, string $subject, string $html): bool
    {
        $logPath = base_path('storage/logs/mail.log');

        $entry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_repeat('-', 60),
            strip_tags($html)
        );

        file_put_contents($logPath, $entry, FILE_APPEND);

        return true;
    }

    private function logError(string $to, string $subject, string $error): void
    {
        $logPath = base_path('storage/logs/mail-error.log');

        $entry = sprintf(
            "[%s] FAILED To: %s | Subject: %s | Error: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $error
        );

        file_put_contents($logPath, $entry, FILE_APPEND);
    }
}