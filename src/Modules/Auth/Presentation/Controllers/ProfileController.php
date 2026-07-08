<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Support\Hash;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Auth\Infrastructure\Persistence\MysqlUserRepository;
use App\Modules\Cart\Infrastructure\Persistence\MysqlAddressRepository;
use App\Modules\Order\Application\Services\OrderService;

class ProfileController
{
    private MysqlUserRepository $userRepo;
    private MysqlAddressRepository $addressRepo;
    private OrderService $orderService;

    public function __construct()
    {
        $this->userRepo    = new MysqlUserRepository();
        $this->addressRepo = new MysqlAddressRepository();
        $this->orderService = new OrderService();
    }

    public function index(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        return Response::make(view('Auth::profile.index', [
            'title' => 'Profil Saya',
            'user'  => $user,
        ]));
    }

    public function update(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $name  = trim((string) $request->input('name'));
        $phone = trim((string) $request->input('phone'));

        if (strlen($name) < 3) {
            Session::flash('error', 'Nama minimal 3 karakter.');
            return Response::redirect('/profil');
        }

        $this->userRepo->updateProfile($user->id, [
            'name'  => $name,
            'phone' => $phone ?: null,
        ]);

        CurrentUserService::clearCache();

        Session::flash('success', 'Profil berhasil diperbarui.');

        return Response::redirect('/profil');
    }

    public function showChangePassword(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        return Response::make(view('Auth::profile.change-password', [
            'title' => 'Ubah Password',
        ]));
    }

    public function updatePassword(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $currentPassword     = (string) $request->input('current_password');
        $newPassword         = (string) $request->input('new_password');
        $newPasswordConfirm  = (string) $request->input('new_password_confirmation');

        if (! Hash::check($currentPassword, $user->password)) {
            Session::flash('error', 'Password saat ini salah.');
            return Response::redirect('/profil/ubah-password');
        }

        if (strlen($newPassword) < 8) {
            Session::flash('error', 'Password baru minimal 8 karakter.');
            return Response::redirect('/profil/ubah-password');
        }

        if ($newPassword !== $newPasswordConfirm) {
            Session::flash('error', 'Konfirmasi password baru tidak cocok.');
            return Response::redirect('/profil/ubah-password');
        }

        $this->userRepo->updatePassword($user->id, Hash::make($newPassword));

        Session::flash('success', 'Password berhasil diubah.');

        return Response::redirect('/profil');
    }

    // ===================== ADDRESSES =====================

    public function addresses(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $addresses = $this->addressRepo->findByUser($user->id);

        return Response::make(view('Auth::profile.addresses', [
            'title'     => 'Alamat Saya',
            'addresses' => $addresses,
        ]));
    }

    public function storeAddress(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $this->addressRepo->create([
            'user_id'        => $user->id,
            'label'          => $request->input('label', 'Rumah'),
            'recipient_name' => trim((string) $request->input('recipient_name')),
            'phone'          => trim((string) $request->input('phone')),
            'address'        => trim((string) $request->input('address')),
            'province'       => $request->input('province'),
            'city'           => $request->input('city'),
            'district'       => $request->input('district'),
            'postal_code'    => trim((string) $request->input('postal_code')),
            'area_id'        => $request->input('area_id'),
            'latitude'       => $request->input('latitude') ?: null,
            'longitude'      => $request->input('longitude') ?: null,
            'is_primary'     => $request->input('is_primary') ? 1 : 0,
        ]);

        Session::flash('success', 'Alamat berhasil ditambahkan.');

        return Response::redirect('/profil/alamat');
    }

    public function updateAddress(Request $request, string $id): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $existing = $this->addressRepo->findById((int) $id, $user->id);

        if (! $existing) {
            Session::flash('error', 'Alamat tidak ditemukan.');
            return Response::redirect('/profil/alamat');
        }

        $this->addressRepo->update((int) $id, $user->id, [
            'label'          => $request->input('label', 'Rumah'),
            'recipient_name' => trim((string) $request->input('recipient_name')),
            'phone'          => trim((string) $request->input('phone')),
            'address'        => trim((string) $request->input('address')),
            'province'       => $request->input('province'),
            'city'           => $request->input('city'),
            'district'       => $request->input('district'),
            'postal_code'    => trim((string) $request->input('postal_code')),
            'area_id'        => $request->input('area_id'),
            'latitude'       => $request->input('latitude') ?: null,
            'longitude'      => $request->input('longitude') ?: null,
            'is_primary'     => $request->input('is_primary') ? 1 : 0,
        ]);

        Session::flash('success', 'Alamat berhasil diperbarui.');

        return Response::redirect('/profil/alamat');
    }

    public function deleteAddress(Request $request, string $id): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $this->addressRepo->delete((int) $id, $user->id);

        Session::flash('success', 'Alamat berhasil dihapus.');

        return Response::redirect('/profil/alamat');
    }

    public function setPrimaryAddress(Request $request, string $id): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $this->addressRepo->setPrimary((int) $id, $user->id);

        Session::flash('success', 'Alamat utama berhasil diubah.');

        return Response::redirect('/profil/alamat');
    }
}