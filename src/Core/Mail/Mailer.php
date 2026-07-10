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
 *                di server — sering diblokir shared hosting, DAN jangan
 *                pernah dipakai untuk kirim lewat Resend — Resend hanya
 *                didukung lewat HTTP API/SMTP relay dengan kredensial
 *                khusus, bukan email pribadi seperti Gmail).
 *   - 'resend' : kirim via Resend HTTP API langsung (curl), sesuai pola
 *                yang sudah terbukti berjalan di project lain. Cuma
 *                butuh port 443 (HTTPS) yang hampir selalu terbuka di
 *                semua hosting.
 *
 * Driver ditentukan dari config('mail')['driver'] — WAJIB diset
 * MAIL_DRIVER=resend di .env supaya driver ini benar-benar dipakai.
 * Kalau .env masih MAIL_DRIVER=smtp, method sendViaResend() di bawah
 * TIDAK AKAN PERNAH terpanggil sama sekali, sekalipun RESEND_API_KEY
 * sudah diisi dengan benar.
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
     * Kirim via Resend HTTP API (curl langsung), persis pola yang sudah
     * terbukti berjalan di project lain (EmailService.php helpdesk).
     */
    private function sendViaResend(string $to, string $subject, string $html, ?string $toName = null): bool
    {
        $apiKey = $this->config['resend']['api_key'] ?? '';
        if ($apiKey === '') {
            $apiKey = $_ENV['RESEND_API_KEY'] ?? getenv('RESEND_API_KEY') ?: '';
        }

        $fromAddress = $this->config['resend']['from_address'] ?? ($this->config['from']['address'] ?? '');
        if ($fromAddress === '') {
            $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: '';
        }

        if ($apiKey === '') {
            $this->logError($to, $subject, 'Resend API key belum dikonfigurasi (config maupun $_ENV kosong).');
            throw new RuntimeException('Resend API key belum dikonfigurasi. Isi RESEND_API_KEY di .env.');
        }

        if ($fromAddress === '') {
            $this->logError($to, $subject, 'Resend from_address belum dikonfigurasi.');
            throw new RuntimeException('Alamat pengirim Resend belum dikonfigurasi.');
        }

        $subjectClean = trim(preg_replace('/\s+/', ' ', $subject));

        $payload = [
            'from'    => $fromAddress,
            'to'      => [$to],
            'subject' => $subjectClean,
            'html'    => $html,
        ];

        $ch = curl_init('https://api.resend.com/emails');

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);

        if ($response === false) {
            curl_close($ch);
            $this->logError($to, $subject, 'Resend cURL error: ' . $curlErr);
            throw new RuntimeException('Gagal mengirim email ke ' . $to . ' via Resend: ' . $curlErr);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logError($to, $subject, "Resend FAIL {$httpCode}: {$response}");
            throw new RuntimeException("Gagal mengirim email ke {$to} via Resend (HTTP {$httpCode}): {$response}");
        }

        return true;
    }

    /**
     * Kirim via PHPMailer + SMTP.
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

            $mail->Timeout       = 10;
            $mail->SMTPKeepAlive = false;

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