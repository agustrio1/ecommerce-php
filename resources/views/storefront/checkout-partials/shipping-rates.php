<?php if (empty($rates)): ?>
    <p class="text-amber-600 text-sm text-center py-3 bg-amber-50 rounded-lg border border-amber-100">
        Tidak ada kurir tersedia untuk rute ini.
    </p>
<?php else: ?>
    <div class="space-y-2">
        <?php foreach ($rates as $i => $rate): ?>
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-400 transition has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                <input type="radio"
                    name="_courier_radio"
                    value="<?= $i ?>"
                    <?= $i === 0 ? 'checked' : '' ?>
                    data-company="<?= e($rate['courier_code']) ?>"
                    data-type="<?= e($rate['courier_service_code'] ?? $rate['type'] ?? '') ?>"
                    data-name="<?= e($rate['courier_name'] . ' ' . $rate['courier_service_name']) ?>"
                    data-cost="<?= (int) $rate['price'] ?>"
                    onchange="(function(el){
                        const root = document.getElementById('checkoutForm')?.closest('[x-data]') || document.querySelector('[x-data]');
                        if (root && window.Alpine) {
                            const data = window.Alpine.$data(root);
                            if (data && typeof data.selectCourier === 'function') {
                                data.selectCourier(el.dataset.company, el.dataset.type, el.dataset.name, el.dataset.cost);
                            }
                        }
                    })(this)"
                    class="text-orange-600 focus:ring-orange-500">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-800">
                            <?= e(strtoupper($rate['courier_code'])) ?> — <?= e($rate['courier_service_name']) ?>
                        </p>
                        <p class="text-sm font-bold text-orange-600 shrink-0">
                            Rp <?= number_format($rate['price'], 0, ',', '.') ?>
                        </p>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Estimasi: <?= e($rate['shipment_duration_range'] ?? $rate['duration'] ?? '-') ?> hari
                    </p>
                </div>
            </label>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        const first = document.querySelector('input[name="_courier_radio"][value="0"]');
        if (!first) return;

        const root = document.getElementById('checkoutForm')?.closest('[x-data]')
            || document.querySelector('[x-data]');

        if (root && window.Alpine) {
            const data = window.Alpine.$data(root);
            if (data && typeof data.selectCourier === 'function') {
                data.selectCourier(
                    first.dataset.company,
                    first.dataset.type,
                    first.dataset.name,
                    first.dataset.cost
                );
            }
        }
    })();
    </script>
<?php endif; ?>
