<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Services;

use App\Core\Support\Hash;
use App\Modules\Auth\Application\DTOs\AuthResult;
use App\Modules\Auth\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Auth\Domain\Repositories\UserRepositoryInterface;
use PDO;
use RuntimeException;

/**
 * AuthService
 *
 * Logic bisnis inti modul Auth: register, login, verifikasi email,
 * lupa password. Tidak tau apa-apa soal HTTP (Request/Response) —
 * itu tugas Controller. Service ini murni orchestration domain logic.
 *
 * PENTING soal performa & kompatibilitas hosting: pengiriman email
 * (verifikasi & reset password) MENCOBA dilakukan secara async lewat
 * proses `php cli` terpisah (exec(... &)) supaya user tidak perlu
 * menunggu SMTP handshake selesai sebelum dapat response.
 *
 * NAMUN banyak shared hosting (cPanel dkk) men-disable exec()/shell_exec()
 * demi keamanan. Kalau itu terjadi, exec() gagal DIAM-DIAM (tidak
 * melempar exception), sehingga email tidak pernah terkirim tanpa ada
 * error yang terlihat — inilah yang menyebabkan "aman di development,
 * tidak terima email di shared hosting staging".
 *
 * dispatch() MENDETEKSI dulu apakah exec() benar-benar bisa dipakai (ada
 * di PHP dan tidak ada di disable_functions). Kalau bisa, jalur async
 * dipakai seperti biasa. Kalau TIDAK bisa, otomatis fallback ke
 * pengiriman SYNCHRONOUS di proses yang sama — memanggil command class
 * yang sama persis (bukan duplikasi logic) secara langsung tanpa exec().
 * User di shared hosting akan sedikit menunggu SMTP/Resend API (beberapa
 * detik), tapi email tetap benar-benar terkirim, alih-alih gagal total
 * tanpa jejak error.
 */
class AuthService
{
    private UserRepositoryInterface $users;
    private RoleRepositoryInterface $roles;
    private PDO $pdo;

    private const VERIFICATION_TOKEN_TTL_MINUTES = 60;
    private const RESET_TOKEN_TTL_MINUTES = 60;

    public function __construct()
    {
        $this->users = new \App\Modules\Auth\Infrastructure\Persistence\MysqlUserRepository();
        $this->roles = new \App\Modules\Auth\Infrastructure\Persistence\MysqlRoleRepository();
        $this->pdo   = db();
    }

    /**
     * Registrasi user baru.
     * Jika ini user PERTAMA di sistem (tabel users masih kosong),
     * otomatis diberi role 'super_admin'. Selain itu, default 'customer'.
     */
    public function register(string $name, string $email, string $password, ?string $phone = null): AuthResult
    {
        if ($this->users->emailExists($email)) {
            return AuthResult::fail('Email sudah terdaftar. Silakan login atau gunakan email lain.');
        }

        $isFirstUser = $this->users->countAll() === 0;
        $roleSlug = $isFirstUser ? 'super_admin' : 'customer';

        $roleId = $this->roles->findIdBySlug($roleSlug);

        if ($roleId === null) {
            throw new RuntimeException("Role [{$roleSlug}] tidak ditemukan. Jalankan 'php cli db:seed' terlebih dahulu.");
        }

        $user = $this->users->create([
            'role_id'  => $roleId,
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => Hash::make($password),
            'is_active' => 1,
        ]);

        $this->sendVerificationEmail($user->id, $user->email, $user->name);

        $message = $isFirstUser
            ? 'Akun Super Admin berhasil dibuat. Silakan cek email untuk verifikasi.'
            : 'Registrasi berhasil. Silakan cek email untuk verifikasi akun Anda.';

        return AuthResult::ok($message, $user->toPublicArray());
    }

    /**
     * Login user. Email WAJIB sudah terverifikasi sebelum bisa login.
     */
    public function login(string $email, string $password): AuthResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! Hash::check($password, $user->password)) {
            return AuthResult::fail('Email atau password salah.');
        }

        if (! $user->isActive) {
            return AuthResult::fail('Akun Anda dinonaktifkan. Hubungi administrator.');
        }

        if (! $user->isEmailVerified()) {
            return AuthResult::fail('Email Anda belum diverifikasi. Silakan cek email atau minta kirim ulang link verifikasi.');
        }

        return AuthResult::ok('Login berhasil.', $user->toPublicArray());
    }

    /**
     * Generate token verifikasi baru dan kirim email (async kalau bisa,
     * fallback synchronous kalau tidak).
     */
    public function sendVerificationEmail(int $userId, string $email, string $name): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::VERIFICATION_TOKEN_TTL_MINUTES . ' minutes'));

        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verification_tokens (user_id, token, expires_at, created_at, updated_at)
             VALUES (:user_id, :token, :expires_at, NOW(), NOW())'
        );
        $stmt->execute(['user_id' => $userId, 'token' => $token, 'expires_at' => $expiresAt]);

        $this->dispatch(
            'mail:send-verification',
            [(string) $userId, $token],
            \App\Core\Console\Commands\MailSendVerificationCommand::class
        );
    }

    /**
     * Verifikasi email berdasarkan token dari link yang diklik user.
     */
    public function verifyEmail(string $token): AuthResult
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM email_verification_tokens WHERE token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if ($row === false) {
            return AuthResult::fail('Token verifikasi tidak valid.');
        }

        if ($row['used_at'] !== null) {
            return AuthResult::fail('Token verifikasi sudah pernah digunakan.');
        }

        if (strtotime($row['expires_at']) < time()) {
            return AuthResult::fail('Token verifikasi sudah kadaluarsa. Silakan minta link baru.');
        }

        $this->users->markEmailAsVerified((int) $row['user_id']);

        $markUsed = $this->pdo->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = :id');
        $markUsed->execute(['id' => $row['id']]);

        return AuthResult::ok('Email berhasil diverifikasi. Silakan login.');
    }

    /**
     * Kirim ulang email verifikasi (untuk user yang belum verifikasi).
     */
    public function resendVerification(string $email): AuthResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            // Pesan generik, tidak bocorkan apakah email terdaftar atau tidak (keamanan).
            return AuthResult::ok('Jika email terdaftar dan belum diverifikasi, kami sudah mengirim ulang link verifikasi.');
        }

        if ($user->isEmailVerified()) {
            return AuthResult::fail('Email ini sudah terverifikasi. Silakan login.');
        }

        $this->sendVerificationEmail($user->id, $user->email, $user->name);

        return AuthResult::ok('Link verifikasi baru sudah dikirim ke email Anda.');
    }

    /**
     * Generate token reset password dan kirim email (async kalau bisa,
     * fallback synchronous kalau tidak).
     */
    public function forgotPassword(string $email): AuthResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return AuthResult::ok('Jika email terdaftar, kami sudah mengirim link reset password.');
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TOKEN_TTL_MINUTES . ' minutes'));

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at, updated_at)
             VALUES (:user_id, :token, :expires_at, NOW(), NOW())'
        );
        $stmt->execute(['user_id' => $user->id, 'token' => $token, 'expires_at' => $expiresAt]);

        $this->dispatch(
            'mail:send-reset-password',
            [(string) $user->id, $token],
            \App\Core\Console\Commands\MailSendResetPasswordCommand::class
        );

        return AuthResult::ok('Jika email terdaftar, kami sudah mengirim link reset password.');
    }

    /**
     * Reset password berdasarkan token dari email.
     */
    public function resetPassword(string $token, string $newPassword): AuthResult
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_reset_tokens WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if ($row === false) {
            return AuthResult::fail('Token reset password tidak valid.');
        }

        if ($row['used_at'] !== null) {
            return AuthResult::fail('Token reset password sudah pernah digunakan.');
        }

        if (strtotime($row['expires_at']) < time()) {
            return AuthResult::fail('Token reset password sudah kadaluarsa. Silakan minta link baru.');
        }

        $this->users->updatePassword((int) $row['user_id'], Hash::make($newPassword));

        $markUsed = $this->pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
        $markUsed->execute(['id' => $row['id']]);

        return AuthResult::ok('Password berhasil direset. Silakan login dengan password baru Anda.');
    }

    /**
     * Cek apakah exec() benar-benar bisa dipakai di environment ini —
     * baik fungsi PHP-nya ada, MAUPUN tidak diblokir lewat disable_functions
     * di php.ini (yang umum terjadi di shared hosting).
     */
    private function execAvailable(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('exec', $disabled, true);
    }

    /**
     * Jalankan command CLI untuk kirim email — ASYNC lewat exec() kalau
     * environment mendukung, atau SYNCHRONOUS langsung di proses ini
     * (memanggil command class yang sama, tanpa shell) kalau exec()
     * diblokir. Kedua jalur memakai logic pengiriman yang PERSIS SAMA
     * (class command yang sama), jadi tidak ada duplikasi behavior.
     *
     * @param class-string $commandClass Command class yang implement
     *                                   handle(array $args): int
     */
    private function dispatch(string $command, array $args, string $commandClass): void
    {
        if ($this->execAvailable()) {
            $phpBinary = PHP_BINARY;
            $cliPath   = base_path('cli');

            $parts = [escapeshellcmd($phpBinary), escapeshellarg($cliPath), escapeshellarg($command)];
            foreach ($args as $arg) {
                $parts[] = escapeshellarg($arg);
            }

            $fullCommand = implode(' ', $parts) . ' > /dev/null 2>&1 &';

            exec($fullCommand);
            return;
        }

        // Fallback: exec() tidak tersedia (umum di shared hosting) —
        // jalankan command yang sama secara SYNCHRONOUS di proses ini.
        // User akan menunggu SMTP/Resend selesai (beberapa detik), tapi
        // email benar-benar terkirim alih-alih gagal diam-diam.
        try {
            $handler = new $commandClass();
            $handler->handle($args);
        } catch (\Throwable $e) {
            // Jangan sampai kegagalan kirim email menggagalkan seluruh
            // proses register()/forgotPassword() — user tetap berhasil
            // register/dapat token, cuma emailnya yang gagal. Catat ke
            // log server supaya bisa diinvestigasi.
            error_log('Gagal mengirim email (' . $command . '): ' . $e->getMessage());
        }
    }
}