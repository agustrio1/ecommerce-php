<?php

declare(strict_types=1);

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use RuntimeException;

/**
 * Mailer
 *
 * Wrapper PHPMailer untuk kirim email via SMTP.
 * Kalau MAIL_DRIVER=log, email tidak benar-benar dikirim, hanya ditulis
 * ke storage/logs/mail.log — berguna untuk development tanpa SMTP aktif.
 *
 * Pemakaian:
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
        if (($this->config['driver'] ?? 'smtp') === 'log') {
            return $this->logOnly($to, $subject, $html);
        }

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