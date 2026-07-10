<?php

declare(strict_types=1);

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Resend;
use RuntimeException;

/**
 * Mailer
 *
 * Wrapper pengiriman email dengan 3 driver:
 *   - 'log'    : tidak benar-benar mengirim, hanya ditulis ke
 *                storage/logs/mail.log — untuk development.
 *   - 'smtp'   : kirim via PHPMailer + SMTP (butuh port 587/465 terbuka
 *                di server — sering diblokir shared hosting).
 *   - 'resend' : kirim via Resend PHP SDK resmi (composer require
 *                resend/resend-php), sesuai dokumentasi resmi di
 *                https://resend.com/docs/send-with-php — cuma butuh
 *                port 443 (HTTPS) yang hampir selalu terbuka di semua
 *                hosting.
 *
 * Driver ditentukan dari config('mail')['driver'].
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
     * Kirim via Resend PHP SDK resmi, sesuai contoh di dokumentasi:
     * https://resend.com/docs/send-with-php
     *
     *   $resend = Resend::client($apiKey);
     *   $resend->emails->send([
     *       'from'    => '...',
     *       'to'      => ['...'],
     *       'subject' => '...',
     *       'html'    => '...',
     *   ]);
     *
     * Butuh `composer require resend/resend-php` sebelum driver ini bisa
     * dipakai.
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

        $fromName = $this->config['resend']['from_name'] ?? ($this->config['from']['name'] ?? '');
        if ($fromName === '') {
            $fromName = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: '';
        }

        if ($apiKey === '') {
            $this->logError($to, $subject, 'Resend API key belum dikonfigurasi (config maupun $_ENV kosong).');
            throw new RuntimeException('Resend API key belum dikonfigurasi. Isi RESEND_API_KEY di .env.');
        }

        if ($fromAddress === '') {
            $this->logError($to, $subject, 'Resend from_address belum dikonfigurasi.');
            throw new RuntimeException('Alamat pengirim Resend belum dikonfigurasi.');
        }

        if (! class_exists(Resend::class)) {
            $this->logError($to, $subject, 'SDK resend/resend-php belum ter-install.');
            throw new RuntimeException('SDK Resend belum ter-install. Jalankan: composer require resend/resend-php');
        }

        $from = $fromName !== '' ? "{$fromName} <{$fromAddress}>" : $fromAddress;
        $subjectClean = trim(preg_replace('/\s+/', ' ', $subject));

        try {
            $resend = Resend::client($apiKey);

            $resend->emails->send([
                'from'    => $from,
                'to'      => [$to],
                'subject' => $subjectClean,
                'html'    => $html,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logError($to, $subject, 'Resend error: ' . $e->getMessage());
            throw new RuntimeException("Gagal mengirim email ke {$to} via Resend: " . $e->getMessage(), 0, $e);
        }
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