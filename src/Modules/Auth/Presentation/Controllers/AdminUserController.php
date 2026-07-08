<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Support\Hash;
use PDO;

class AdminUserController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $roleId = $request->query('role', '');

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]          = '(u.name LIKE :search OR u.email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($roleId !== '') {
            $where[]        = 'u.role_id = :role_id';
            $params['role_id'] = (int) $roleId;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE {$whereSql}
             ORDER BY u.created_at DESC"
        );
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $roles = $this->pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();

        return Response::make(view('Auth::admin.users', [
            'title'      => 'Pengguna',
            'users'      => $users,
            'roles'      => $roles,
            'search'     => $search,
            'roleFilter' => $roleId,
        ]));
    }

    public function create(Request $request): Response
    {
        $roles = $this->pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();

        return Response::make(view('Auth::admin.user-form', [
            'title' => 'Tambah Pengguna',
            'roles' => $roles,
            'user'  => null,
        ]));
    }

    public function store(Request $request): Response
    {
        $name     = trim((string) $request->input('name'));
        $email    = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $roleId   = (int) $request->input('role_id');

        // Cek email unik
        $exists = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            Session::flash('error', 'Email sudah digunakan.');
            return Response::redirect('/admin/users/create');
        }

        // PENTING (keamanan): sama seperti update(), cegah admin biasa
        // membuat akun baru dengan role super_admin.
        $currentRoleSlug = \App\Modules\Auth\Application\Services\CurrentUserService::roleSlug();
        $targetRole = $this->pdo->prepare('SELECT slug FROM roles WHERE id = :id LIMIT 1');
        $targetRole->execute(['id' => $roleId]);
        $targetRoleRow = $targetRole->fetch();

        if (! $targetRoleRow) {
            Session::flash('error', 'Role tidak valid.');
            return Response::redirect('/admin/users/create');
        }

        if ($targetRoleRow['slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') {
            Session::flash('error', 'Hanya Super Admin yang bisa membuat akun dengan role Super Admin.');
            return Response::redirect('/admin/users/create');
        }

        $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role_id, email_verified_at, created_at, updated_at)
             VALUES (:name, :email, :password, :role_id, NOW(), NOW(), NOW())'
        )->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role_id'  => $roleId,
        ]);

        Session::flash('success', 'Pengguna berhasil ditambahkan.');

        return Response::redirect('/admin/users');
    }

    public function edit(Request $request, string $id): Response
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if (! $user) {
            return Response::notFound('Pengguna tidak ditemukan.');
        }

        $roles = $this->pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();

        return Response::make(view('Auth::admin.user-form', [
            'title' => 'Edit Pengguna',
            'roles' => $roles,
            'user'  => $user,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $currentUser = \App\Modules\Auth\Application\Services\CurrentUserService::user();
        $currentRoleSlug = \App\Modules\Auth\Application\Services\CurrentUserService::roleSlug();

        $name    = trim((string) $request->input('name'));
        $roleId  = (int) $request->input('role_id');
        $password = (string) $request->input('password');

        // PENTING (keamanan): hanya super_admin yang boleh menetapkan role
        // super_admin ke user manapun (termasuk dirinya sendiri). Tanpa ini,
        // user dengan role 'admin' biasa bisa menaikkan levelnya sendiri
        // jadi super_admin lewat form edit user ini.
        $targetRole = $this->pdo->prepare('SELECT slug FROM roles WHERE id = :id LIMIT 1');
        $targetRole->execute(['id' => $roleId]);
        $targetRoleRow = $targetRole->fetch();

        if (! $targetRoleRow) {
            Session::flash('error', 'Role tidak valid.');
            return Response::redirect("/admin/users/{$id}/edit");
        }

        if ($targetRoleRow['slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') {
            Session::flash('error', 'Hanya Super Admin yang bisa menetapkan role Super Admin.');
            return Response::redirect("/admin/users/{$id}/edit");
        }

        $params = ['name' => $name, 'role_id' => $roleId, 'id' => $id];
        $sets   = ['name = :name', 'role_id = :role_id', 'updated_at = NOW()'];

        if ($password !== '') {
            $sets[]              = 'password = :password';
            $params['password']  = Hash::make($password);
        }

        $this->pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        Session::flash('success', 'Pengguna berhasil diperbarui.');

        return Response::redirect('/admin/users');
    }

    public function destroy(Request $request, string $id): Response
    {
        // Jangan hapus diri sendiri
        $currentId = \App\Modules\Auth\Application\Services\CurrentUserService::user()?->id;
        if ((int) $id === $currentId) {
            Session::flash('error', 'Tidak bisa menghapus akun sendiri.');
            return Response::redirect('/admin/users');
        }

        $this->pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);

        Session::flash('success', 'Pengguna berhasil dihapus.');

        return Response::redirect('/admin/users');
    }
}