<?php $this->layout('layouts.admin', ['title' => 'Pengguna']) ?>

<?php $this->section('content') ?>

<?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
<?php $flashError   = \App\Core\Http\Session::getFlash('error'); ?>
<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-900">Pengguna</h1>
    <a href="/admin/users/create"
        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Pengguna
    </a>
</div>

<form method="GET" action="/admin/users" class="flex flex-col sm:flex-row gap-2 mb-5">
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="search" value="<?= e($search) ?>"
            placeholder="Cari nama atau email..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
    </div>
    <select name="role" class="w-full sm:w-40 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
        <option value="">Semua Role</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?= $role['id'] ?>" <?= $roleFilter == $role['id'] ? 'selected' : '' ?>>
                <?= e($role['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Filter
    </button>
</form>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Bergabung</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-orange-700 font-bold text-sm"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 truncate"><?= e($user['name']) ?></p>
                                    <p class="text-xs text-gray-400 truncate"><?= e($user['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <?php
                            $roleColor = match ($user['role_slug'] ?? '') {
                                'super_admin' => 'bg-purple-100 text-purple-700',
                                'admin'       => 'bg-blue-100 text-blue-700',
                                default       => 'bg-gray-100 text-gray-600',
                            };
                            ?>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $roleColor ?>">
                                <?= e($user['role_name']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <?php if ($user['email_verified_at']): ?>
                                <span class="flex items-center gap-1 text-xs text-green-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Terverifikasi
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-amber-600">Belum Verifikasi</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 hidden lg:table-cell">
                            <?= e(date('d M Y', strtotime($user['created_at']))) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="/admin/users/<?= $user['id'] ?>/edit"
                                    class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                    Edit
                                </a>
                                <form method="POST" action="/admin/users/<?= $user['id'] ?>"
                                    onsubmit="return confirm('Hapus pengguna ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400 text-sm">Belum ada pengguna.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>