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
 * PENTING soal performa: pengiriman email (verifikasi & reset password)
 * TIDAK dilakukan secara synchronous di sini. Kirim email lewat SMTP bisa
 * makan waktu beberapa detik (handshake, TLS, auth), dan kalau dilakukan
 * langsung di alur register()/forgotPassword(), user harus nunggu semua
 * itu selesai sebelum dapat response. Sebagai gantinya, token dibuat &
 * disimpan secara sync (cepat, cuma insert DB), lalu pengiriman email
 * dilempar ke proses `php cli` terpisah yang jalan di BACKGROUND lewat
 * exec(... &) — request utama langsung lanjut tanpa menunggu proses itu.
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
     * Generate token verifikasi baru dan JADWALKAN pengiriman email
     * (bukan kirim langsung). $email dan $name dipertahankan di signature
     * untuk kompatibilitas pemanggil lama, meski proses kirim email yang
     * sebenarnya mengambil ulang data user dari database di command CLI.
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

        $this->dispatchAsync('mail:send-verification', [(string) $userId, $token]);
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
     * Generate token reset password dan JADWALKAN pengiriman email
     * (bukan kirim langsung, sama seperti sendVerificationEmail()).
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

        $this->dispatchAsync('mail:send-reset-password', [(string) $user->id, $token]);

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
     * Jalankan command `php cli {command} {args...}` sebagai proses
     * TERPISAH di background. Tanda "&" di akhir command memastikan shell
     * tidak menunggu proses itu selesai, sehingga exec() langsung return
     * dan kode di AuthService bisa lanjut tanpa delay SMTP.
     *
     * CATATAN: exec() harus tidak berada dalam disable_functions di php.ini.
     * Di Termux/php -S bawaan, exec() biasanya aktif secara default.
     */
    private function dispatchAsync(string $command, array $args = []): void
    {
        $phpBinary = PHP_BINARY;
        $cliPath   = base_path('cli');

        $parts = [escapeshellcmd($phpBinary), escapeshellarg($cliPath), escapeshellarg($command)];
        foreach ($args as $arg) {
            $parts[] = escapeshellarg($arg);
        }

        $fullCommand = implode(' ', $parts) . ' > /dev/null 2>&1 &';

        exec($fullCommand);
    }
}