<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database\Seeder;

/**
 * RolePermissionSeeder
 *
 * Mengisi data awal: roles, permissions, dan mapping role_permissions.
 * Role 'super_admin' otomatis mendapat SEMUA permission yang ada.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = $this->seedRoles();
        $permissionIds = $this->seedPermissions();

        $this->assignAllPermissionsToSuperAdmin($roleIds['super_admin'], $permissionIds);
        $this->assignAdminPermissions($roleIds['admin'], $permissionIds);
    }

    /**
     * @return array<string, int> slug => id
     */
    private function seedRoles(): array
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Akses penuh ke seluruh sistem'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Mengelola operasional toko sehari-hari'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'Pembeli / pelanggan toko'],
        ];

        $ids = [];

        foreach ($roles as $role) {
            $existing = $this->pdo->prepare('SELECT id FROM roles WHERE slug = :slug');
            $existing->execute(['slug' => $role['slug']]);
            $id = $existing->fetchColumn();

            if ($id === false) {
                $id = $this->insert('roles', [
                    'name'        => $role['name'],
                    'slug'        => $role['slug'],
                    'description' => $role['description'],
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }

            $ids[$role['slug']] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return array<string, int> slug => id
     */
    private function seedPermissions(): array
    {
        $modules = ['products', 'categories', 'customers', 'carts', 'orders', 'payments', 'shippings', 'inventories', 'reviews', 'users', 'roles'];
        $actions = ['view', 'create', 'edit', 'delete'];

        $ids = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $name = ucfirst($action) . ' ' . ucfirst($module);

                $existing = $this->pdo->prepare('SELECT id FROM permissions WHERE slug = :slug');
                $existing->execute(['slug' => $slug]);
                $id = $existing->fetchColumn();

                if ($id === false) {
                    $id = $this->insert('permissions', [
                        'name'        => $name,
                        'slug'        => $slug,
                        'module'      => $module,
                        'description' => "Izin untuk {$action} pada module {$module}",
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                }

                $ids[$slug] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * Super Admin dapat SEMUA permission tanpa terkecuali.
     */
    private function assignAllPermissionsToSuperAdmin(int $roleId, array $permissionIds): void
    {
        foreach ($permissionIds as $permissionId) {
            $this->attachPermission($roleId, $permissionId);
        }
    }

    /**
     * Admin dapat semua permission KECUALI yang berkaitan dengan manajemen roles
     * dan permission delete user (kebijakan default, bisa diubah lewat dashboard nanti).
     */
    private function assignAdminPermissions(int $roleId, array $permissionIds): void
    {
        foreach ($permissionIds as $slug => $permissionId) {
            if (str_starts_with($slug, 'roles.')) {
                continue;
            }

            if ($slug === 'users.delete') {
                continue;
            }

            $this->attachPermission($roleId, $permissionId);
        }
    }

    private function attachPermission(int $roleId, int $permissionId): void
    {
        $existing = $this->pdo->prepare(
            'SELECT id FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id'
        );
        $existing->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);

        if ($existing->fetchColumn() === false) {
            $this->insert('role_permissions', [
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}