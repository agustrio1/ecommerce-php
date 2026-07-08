<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Auth\Application\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // ===================== REGISTER =====================

    public function showRegister(Request $request): Response
    {
        return Response::make(view('Auth::register'));
    }

    public function register(Request $request): Response
    {
        $name     = trim((string) $request->input('name'));
        $email    = trim(strtolower((string) $request->input('email')));
        $phone    = trim((string) $request->input('phone'));
        $password = (string) $request->input('password');
        $passwordConfirm = (string) $request->input('password_confirmation');

        $errors = $this->validateRegister($name, $email, $password, $passwordConfirm);

        if (! empty($errors)) {
            return $this->backWithErrors($request, $errors, 'Auth::register');
        }

        $result = $this->authService->register($name, $email, $password, $phone ?: null);

        if (! $result->success) {
            return $this->backWithErrors($request, ['general' => $result->message], 'Auth::register');
        }

        Session::flash('success', $result->message);

        return Response::redirect('/login');
    }

    // ===================== LOGIN =====================

    public function showLogin(Request $request): Response
    {
        return Response::make(view('Auth::login'));
    }

    public function login(Request $request): Response
    {
        $email    = trim(strtolower((string) $request->input('email')));
        $password = (string) $request->input('password');

        if ($email === '' || $password === '') {
            return $this->backWithErrors($request, ['general' => 'Email dan password wajib diisi.'], 'Auth::login');
        }

        $result = $this->authService->login($email, $password);

        if (! $result->success) {
            return $this->backWithErrors($request, ['general' => $result->message], 'Auth::login');
        }

        // PENTING: gabungkan cart guest (kalau ada isinya) ke akun user
        // SEBELUM regenerate session ID. Session::loginUserId() di bawah
        // memanggil regenerate(), yang mengganti session_id() — kalau
        // merge dilakukan setelahnya, cart guest yang terikat ke
        // session_id() lama sudah tidak bisa dicari lagi lewat
        // session_id() yang baru.
        $this->mergeGuestCartIntoUser((int) $result->user['id']);

        \App\Modules\Auth\Application\Services\CurrentUserService::clearCache();
        Session::loginUserId($result->user['id']);

        $roleSlug = \App\Modules\Auth\Application\Services\CurrentUserService::roleSlug();

        $redirectTo = in_array($roleSlug, ['super_admin', 'admin'], true) ? '/dashboard' : '/';

        return Response::redirect($redirectTo);
    }

    /**
     * Gabungkan isi cart guest (terikat ke session_id saat ini, user_id
     * NULL) ke dalam cart milik user yang baru login. Kalau user sudah
     * punya cart sebelumnya (dari sesi login lama di device lain, misal),
     * item dari cart guest ditambahkan (quantity dijumlah kalau variant
     * sama) ke cart user tersebut, lalu cart guest dihapus.
     *
     * Tanpa ini, produk yang sudah dimasukkan ke cart sebagai guest akan
     * "hilang" begitu user login, karena CartService::getCartId() setelah
     * login akan mencari/membuat cart baru yang di-scope ke user_id, bukan
     * cart lama yang di-scope ke session_id saja.
     */
    private function mergeGuestCartIntoUser(int $userId): void
    {
        $pdo = db();
        $sessionId = session_id();

        $guestCart = $pdo->prepare(
            'SELECT id FROM carts WHERE session_id = :session_id AND user_id IS NULL LIMIT 1'
        );
        $guestCart->execute(['session_id' => $sessionId]);
        $guestCartRow = $guestCart->fetch();

        if (! $guestCartRow) {
            return; // Tidak ada cart guest untuk sesi ini, tidak ada yang perlu digabung.
        }

        $guestCartId = (int) $guestCartRow['id'];

        $userCart = $pdo->prepare(
            'SELECT id FROM carts WHERE user_id = :user_id ORDER BY id DESC LIMIT 1'
        );
        $userCart->execute(['user_id' => $userId]);
        $userCartRow = $userCart->fetch();

        if (! $userCartRow) {
            // User belum punya cart sama sekali — cukup "adopsi" cart guest
            // ini jadi milik user tersebut, tidak perlu merge item satu-satu.
            $adopt = $pdo->prepare('UPDATE carts SET user_id = :user_id, session_id = :session_id, updated_at = NOW() WHERE id = :id');
            $adopt->execute(['user_id' => $userId, 'session_id' => $sessionId, 'id' => $guestCartId]);
            return;
        }

        $userCartId = (int) $userCartRow['id'];

        if ($userCartId === $guestCartId) {
            return; // Sudah cart yang sama, tidak ada yang perlu digabung.
        }

        // Gabungkan tiap item guest cart ke user cart.
        $guestItems = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = :cart_id');
        $guestItems->execute(['cart_id' => $guestCartId]);

        foreach ($guestItems->fetchAll() as $item) {
            $existing = $pdo->prepare(
                'SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND variant_id = :variant_id LIMIT 1'
            );
            $existing->execute(['cart_id' => $userCartId, 'variant_id' => $item['variant_id']]);
            $existingRow = $existing->fetch();

            if ($existingRow) {
                $newQty = (int) $existingRow['quantity'] + (int) $item['quantity'];
                $update = $pdo->prepare(
                    'UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE id = :id'
                );
                $update->execute(['quantity' => $newQty, 'id' => $existingRow['id']]);
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price, created_at, updated_at)
                     VALUES (:cart_id, :product_id, :variant_id, :quantity, :price, NOW(), NOW())'
                );
                $insert->execute([
                    'cart_id'    => $userCartId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }
        }

        // Hapus cart guest yang sudah digabungkan.
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id')->execute(['cart_id' => $guestCartId]);
        $pdo->prepare('DELETE FROM carts WHERE id = :id')->execute(['id' => $guestCartId]);
    }

    public function logout(Request $request): Response
    {
        Session::logout();
        \App\Modules\Auth\Application\Services\CurrentUserService::clearCache();

        return Response::redirect('/login');
    }

    // ===================== EMAIL VERIFICATION =====================

    public function verifyEmail(Request $request): Response
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return Response::make('Token verifikasi tidak ditemukan di URL.', 400);
        }

        $result = $this->authService->verifyEmail($token);

        Session::flash($result->success ? 'success' : 'error', $result->message);

        return Response::redirect('/login');
    }

    public function showResendVerification(Request $request): Response
    {
        return Response::make(view('Auth::resend-verification'));
    }

    public function resendVerification(Request $request): Response
    {
        $email = trim(strtolower((string) $request->input('email')));

        $result = $this->authService->resendVerification($email);

        Session::flash('success', $result->message);

        return Response::redirect('/resend-verification');
    }

    // ===================== FORGOT / RESET PASSWORD =====================

    public function showForgotPassword(Request $request): Response
    {
        return Response::make(view('Auth::forgot-password'));
    }

    public function forgotPassword(Request $request): Response
    {
        $email = trim(strtolower((string) $request->input('email')));

        $result = $this->authService->forgotPassword($email);

        Session::flash('success', $result->message);

        return Response::redirect('/forgot-password');
    }

    public function showResetPassword(Request $request): Response
    {
        $token = (string) $request->query('token', '');

        return Response::make(view('Auth::reset-password', ['token' => $token]));
    }

    public function resetPassword(Request $request): Response
    {
        $token    = (string) $request->input('token');
        $password = (string) $request->input('password');
        $passwordConfirm = (string) $request->input('password_confirmation');

        if (strlen($password) < 8) {
            return $this->backWithErrors($request, ['general' => 'Password minimal 8 karakter.'], 'Auth::reset-password', ['token' => $token]);
        }

        if ($password !== $passwordConfirm) {
            return $this->backWithErrors($request, ['general' => 'Konfirmasi password tidak cocok.'], 'Auth::reset-password', ['token' => $token]);
        }

        $result = $this->authService->resetPassword($token, $password);

        Session::flash($result->success ? 'success' : 'error', $result->message);

        return Response::redirect($result->success ? '/login' : '/forgot-password');
    }

    // ===================== HELPERS =====================

    private function validateRegister(string $name, string $email, string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (strlen($name) < 3) {
            $errors['name'] = 'Nama minimal 3 karakter.';
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        if ($password !== $passwordConfirm) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak cocok.';
        }

        return $errors;
    }

    private function backWithErrors(Request $request, array $errors, string $view, array $extra = []): Response
    {
        if ($request->isHtmx() || $request->wantsJson()) {
            return Response::json(['errors' => $errors], 422);
        }

        Session::flash('errors', $errors);
        Session::flash('old', $request->all());

        return Response::make(view($view, $extra));
    }
}