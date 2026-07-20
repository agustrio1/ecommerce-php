<?php require_once __DIR__ . '/../_brand.php'; ?>
<?php $brand = nexaroBrandTokens(); ?>

<?php if (empty($rates)): ?>
    <p class="text-sm text-center py-3 rounded-lg border" style="color: <?= e($brand['clay']) ?>; background-color: <?= e($brand['stone']) ?>; border-color: <?= e($brand['line']) ?>;">
        Tidak ada kurir tersedia untuk rute ini.
    </p>
<?php else: ?>
    <div class="space-y-2"
        x-data="{
            selected: 0,
            rates: <?= htmlspecialchars(json_encode(array_map(fn ($r) => [
                'company' => $r['courier_code'],
                'type'    => $r['courier_service_code'] ?? ($r['type'] ?? ''),
                'name'    => $r['courier_name'] . ' ' . $r['courier_service_name'],
                'cost'    => (int) $r['price'],
            ], $rates)), ENT_QUOTES, 'UTF-8') ?>,
            init() {
                if (this.rates.length > 0) {
                    this.choose(0);
                }
            },
            choose(i) {
                this.selected = i;
                const r = this.rates[i];
                if (r) {
                    this.$dispatch('courier-selected', { company: r.company, type: r.type, name: r.name, cost: r.cost });
                }
            }
        }">
        <?php foreach ($rates as $i => $rate): ?>
            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                :style="selected === <?= $i ?>
                    ? 'border-color: <?= e($brand['clay']) ?>; background-color: <?= e($brand['stone']) ?>;'
                    : 'border-color: <?= e($brand['line']) ?>; background-color: #fff;'">
                <input type="radio"
                    name="_courier_radio"
                    value="<?= $i ?>"
                    @change="choose(<?= $i ?>)"
                    :checked="selected === <?= $i ?>"
                    class="focus:outline-none focus-visible:ring-2"
                    style="accent-color: <?= e($brand['clay']) ?>;">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium" style="color: <?= e($brand['ink']) ?>;">
                            <?= e(strtoupper($rate['courier_code'])) ?> — <?= e($rate['courier_service_name']) ?>
                        </p>
                        <p class="text-sm font-bold shrink-0" style="color: <?= e($brand['clay']) ?>;">
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
<?php endif; ?>
