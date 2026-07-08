<?php $this->layout('layouts.admin', ['title' => 'Role & Akses']) ?>

<?php $this->section('content') ?>

<?php $flashSuccess = \App\Core\Http\Session::getFlash('success'); ?>
<?php if ($flashSuccess): ?>
    <div class="mb-4 p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-200"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<div class="mb-5">
    <h1 class="text-xl font-bold text-gray-900">Role & Akses</h1>
    <p class="text-sm text-gray-400">Kelola permission untuk setiap role pengguna</p>
</div>

<!-- Ringkasan role -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <?php foreach ($roles as $role): ?>
        <?php
        $roleColor = match ($role['slug'] ?? '') {
            'super_admin' => 'border-purple-200 bg-purple-50',
            'admin'       => 'border-blue-200 bg-blue-50',
            default       => 'border-gray-200 bg-gray-50',
        };
        $badgeColor = match ($role['slug'] ?? '') {
            'super_admin' => 'bg-purple-100 text-purple-700',
            'admin'       => 'bg-blue-100 text-blue-700',
            default       => 'bg-gray-100 text-gray-600',
        };
        ?>
        <div class="rounded-xl border <?= $roleColor ?> p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $badgeColor ?>">
                    <?= e($role['name']) ?>
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900">
                <?= $role['user_count'] ?>
                <span class="text-sm font-normal text-gray-400">pengguna</span>
            </p>
            <p class="text-xs text-gray-400"><?= $role['permission_count'] ?> permission aktif</p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Permission matrix per role -->
<?php foreach ($roles as $role): ?>
    <div class="bg-white rounded-xl border border-gray-200 mb-5 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-gray-800"><?= e($role['name']) ?></h2>
                <span class="text-xs text-gray-400 font-mono"><?= e($role['slug']) ?></span>
            </div>
            <?php if ($role['slug'] === 'super_admin'): ?>
                <span class="text-xs text-purple-600 font-medium">Semua akses (tidak bisa diubah)</span>
            <?php endif; ?>
        </div>

        <div class="p-5">
            <?php if ($role['slug'] === 'super_admin'): ?>
                <p class="text-sm text-gray-400">
                    Super admin memiliki semua permission secara default.
                </p>
            <?php else: ?>
                <form method="POST" action="/admin/roles/<?= $role['id'] ?>/permissions">
                    <?= csrf_field() ?>

                    <div class="space-y-5">
                        <?php foreach ($permsByModule as $module => $perms): ?>
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        <?= e($module) ?>
                                    </p>
                                    <div class="flex-1 h-px bg-gray-100"></div>
                                    <!-- Centang semua dalam module ini -->
                                    <button type="button"
                                        onclick="toggleModule(this, '<?= e($role['id']) ?>_<?= e($module) ?>')"
                                        class="text-xs text-orange-600 hover:underline">
                                        Pilih semua
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2"
                                    id="module_<?= e($role['id']) ?>_<?= e($module) ?>">
                                    <?php foreach ($perms as $perm): ?>
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox"
                                                name="permissions[]"
                                                value="<?= $perm['id'] ?>"
                                                <?= isset($rpMap[$role['id']][$perm['id']]) ? 'checked' : '' ?>
                                                class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                            <span class="text-sm text-gray-700 group-hover:text-orange-600 transition capitalize">
                                                <?= e($perm['action']) ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-5 border-t border-gray-100 mt-5 flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            Centang permission yang diizinkan untuk role
                            <strong><?= e($role['name']) ?></strong>
                        </p>
                        <button type="submit"
                            class="px-5 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">
                            Simpan Permission
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<script>
function toggleModule(btn, moduleId) {
    const container = document.getElementById('module_' + moduleId);
    if (!container) return;

    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Pilih semua' : 'Hapus semua';
}
</script>

<?php $this->endSection() ?>