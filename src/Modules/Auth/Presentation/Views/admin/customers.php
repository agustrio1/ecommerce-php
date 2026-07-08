<?php $this->layout('layouts.admin', ['title' => 'Pelanggan']) ?>

<?php $this->section('content') ?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Pelanggan</h1>
        <p class="text-sm text-gray-400"><?= $total ?> pelanggan terdaftar</p>
    </div>
    <a href="/admin/export/customers" class="px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>

<form method="GET" action="/admin/customers" class="flex gap-2 mb-5">
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text" name="search" value="<?= e($search) ?>"
            placeholder="Cari nama, email, atau telepon..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
    </div>
    <button type="submit" class="px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Cari
    </button>
</form>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pelanggan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Telepon</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Total Order</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Total Belanja</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Bergabung</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-orange-700 font-bold text-sm">
                                        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 truncate"><?= e($customer['name']) ?></p>
                                    <p class="text-xs text-gray-400 truncate"><?= e($customer['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 hidden md:table-cell">
                            <?= e($customer['phone'] ?? '-') ?>
                        </td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                            <span class="text-sm font-semibold text-gray-800"><?= $customer['total_orders'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-right hidden sm:table-cell">
                            <span class="text-sm font-bold text-gray-900">
                                Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 hidden lg:table-cell">
                            <?= e(date('d M Y', strtotime($customer['created_at']))) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="/admin/customers/<?= $customer['id'] ?>"
                                class="px-3 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center text-gray-400 text-sm">
                            Belum ada pelanggan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php $totalPages = (int) ceil($total / $perPage); ?>
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1.5 mt-5">
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="/admin/customers?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium
                       <?= $p === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-200 hover:bg-gray-50' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php $this->endSection() ?>