<?php

declare(strict_types=1);

namespace App\Modules\Auth\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use PDO;

class AdminRoleController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function index(Request $request): Response
    {
        $roles = $this->pdo->query(
            'SELECT r.*,
                (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count,
                (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
             FROM roles r ORDER BY r.id'
        )->fetchAll();

        // Kolom yang benar: id, name, slug, module, description
        // Parse action dari slug (format: module.action)
        $permissions = $this->pdo->query(
            'SELECT * FROM permissions ORDER BY module, slug'
        )->fetchAll();

        // Group by module, extract action dari slug
        $permsByModule = [];
        foreach ($permissions as $perm) {
            // slug format: "products.view" → action = "view"
            $parts  = explode('.', $perm['slug']);
            $action = $parts[1] ?? $perm['slug'];
            $permsByModule[$perm['module']][] = array_merge($perm, ['action' => $action]);
        }

        // Ambil role_permissions
        $rolePerms = $this->pdo->query('SELECT role_id, permission_id FROM role_permissions')->fetchAll();
        $rpMap = [];
        foreach ($rolePerms as $rp) {
            $rpMap[$rp['role_id']][$rp['permission_id']] = true;
        }

        return Response::make(view('Auth::admin.roles', [
            'title'         => 'Role & Akses',
            'roles'         => $roles,
            'permsByModule' => $permsByModule,
            'rpMap'         => $rpMap,
        ]));
    }

    public function updatePermissions(Request $request, string $roleId): Response
    {
        $permissionIds = $request->input('permissions', []);

        if (! is_array($permissionIds)) {
            $permissionIds = [];
        }

        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')
            ->execute(['role_id' => $roleId]);

        if (! empty($permissionIds)) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
            );
            foreach ($permissionIds as $permId) {
                $stmt->execute(['role_id' => $roleId, 'permission_id' => (int) $permId]);
            }
        }

        Session::flash('success', 'Permission role berhasil diperbarui.');

        return Response::redirect('/admin/roles');
    }
}