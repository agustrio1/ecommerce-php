<?php $this->layout('layouts.admin', ['title' => 'Webhook Logs']) ?>

<?php $this->section('content') ?>

<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-900">Webhook Logs</h1>
    <p class="text-sm text-gray-400"><?= $total ?> log</p>
</div>

<form method="GET" action="/admin/webhook-logs" class="flex flex-col sm:flex-row gap-2 mb-5">
    <select name="source" class="w-full sm:w-44 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
        <option value="">Semua Source</option>
        <option value="ipaymu" <?= $sourceFilter === 'ipaymu' ? 'selected' : '' ?>>iPaymu</option>
        <option value="biteship" <?= $sourceFilter === 'biteship' ? 'selected' : '' ?>>Biteship</option>
    </select>
    <select name="status" class="w-full sm:w-44 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
        <option value="">Semua Status</option>
        <option value="processed" <?= $statusFilter === 'processed' ? 'selected' : '' ?>>Processed</option>
        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="received" <?= $statusFilter === 'received' ? 'selected' : '' ?>>Received</option>
    </select>
    <button type="submit" class="px-5 py-2 bg-gray-800 text-white text-sm rounded-xl font-medium hover:bg-gray-900 transition">
        Filter
    </button>
</form>

<div class="space-y-3">
    <?php foreach ($logs as $log): ?>
        <?php
        $statusColor = match ($log['status']) {
            'processed' => 'bg-green-100 text-green-700',
            'failed'    => 'bg-red-100 text-red-700',
            default     => 'bg-gray-100 text-gray-600',
        };
        ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4" x-data="{ expanded: false }">
            <div class="flex items-center justify-between gap-3 cursor-pointer" @click="expanded = !expanded">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $log['source'] === 'ipaymu' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?>">
                        <?= e(strtoupper($log['source'])) ?>
                    </span>
                    <?php if ($log['event']): ?>
                        <span class="text-xs text-gray-500 font-mono"><?= e($log['event']) ?></span>
                    <?php endif; ?>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $statusColor ?>">
                        <?= e(ucfirst($log['status'])) ?>
                    </span>
                </div>
                <span class="text-xs text-gray-400"><?= e($log['created_at']) ?></span>
            </div>

            <?php if ($log['reference']): ?>
                <p class="text-xs text-gray-400 mt-1 font-mono">Ref: <?= e($log['reference']) ?></p>
            <?php endif; ?>

            <?php if ($log['error_message']): ?>
                <p class="text-xs text-red-600 mt-1"><?= e($log['error_message']) ?></p>
            <?php endif; ?>

            <div x-show="expanded" class="mt-3 pt-3 border-t border-gray-100">
                <pre class="text-xs bg-gray-50 p-3 rounded-lg overflow-x-auto"><?= e(json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($logs)): ?>
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <p class="text-gray-400 text-sm">Belum ada webhook log.</p>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>