<?php $this->layout('layouts.storefront', ['title' => 'Keranjang Belanja']) ?>

<?php $this->section('content') ?>

<div class="py-4" x-data="cartPage()">

    <h1 class="font-bold text-gray-900 text-lg mb-4">Keranjang Belanja</h1>

    <?php $flashError = \App\Core\Http\Session::getFlash('error'); ?>
    <?php if ($flashError): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200">
            <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-400 mb-4">Keranjang belanja kosong</p>
            <a href="/produk" class="px-6 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition">
                Mulai Belanja
            </a>
        </div>
    <?php else: ?>

        <!-- List item -->
        <div class="space-y-3 mb-4" id="cart-items-wrapper">
            <?php foreach ($items as $item): ?>
                <?php
                // Info flash sale khusus untuk item ini (badge, harga coret).
                // Harga item->price di sini SUDAH benar & up-to-date karena
                // CartService::getItems() mensinkronkannya dengan status flash
                // sale terkini sebelum sampai ke controller/view ini.
                $fsInfo      = $flashSaleInfo[$item->variantId] ?? null;
                $isFlashSale = $fsInfo['is_flash_sale'] ?? false;
                $originalPrice = $fsInfo['original_price'] ?? null;
                ?>
                <div class="bg-white rounded-xl border border-gray-100 p-4 flex gap-3 <?= $isFlashSale ? 'border-red-200' : '' ?>"
                    id="cart-item-<?= $item->variantId ?>"
                    x-data="cartItem(<?= $item->variantId ?>, <?= $item->quantity ?>, <?= $item->price ?>)">

                    <!-- Gambar produk -->
                    <a href="/produk/<?= e($item->productSku) ?>" class="shrink-0">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden relative">
                            <?php if ($item->productImage): ?>
                                <img src="/storage/<?= e($item->productImage) ?>"
                                    alt="<?= e($item->productName) ?>"
                                    class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <?php if ($isFlashSale): ?>
                                <div class="absolute top-0.5 left-0.5 bg-red-600 text-white text-[9px] font-bold px-1 py-0.5 rounded">
                                    Sale
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Info produk -->
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm line-clamp-2 mb-0.5">
                            <?= e($item->productName) ?>
                        </p>
                        <?php if ($item->variantLabel !== 'Default'): ?>
                            <p class="text-xs text-gray-400 mb-1"><?= e($item->variantLabel) ?></p>
                        <?php endif; ?>

                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="text-sm font-bold <?= $isFlashSale ? 'text-red-600' : 'text-orange-600' ?>">
                                Rp <?= number_format($item->price, 0, ',', '.') ?>
                            </p>
                            <?php if ($isFlashSale && $originalPrice && $originalPrice > $item->price): ?>
                                <span class="text-xs text-gray-400 line-through">
                                    Rp <?= number_format($originalPrice, 0, ',', '.') ?>
                                </span>
                                <span class="text-[10px] font-semibold text-red-600 bg-red-50 px-1 py-0.5 rounded">
                                    Flash Sale
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Qty control + hapus -->
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    @click="decrease()"
                                    :disabled="loading"
                                    class="w-7 h-7 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition text-lg leading-none disabled:opacity-50">
                                    −
                                </button>
                                <span class="text-sm font-semibold w-6 text-center" x-text="qty"></span>
                                <button type="button"
                                    @click="increase()"
                                    :disabled="loading"
                                    class="w-7 h-7 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition text-lg leading-none disabled:opacity-50">
                                    +
                                </button>
                            </div>

                            <div class="flex items-center gap-3">
                                <p class="text-sm font-bold text-gray-900" x-text="'Rp ' + subtotal()"></p>
                                <button type="button"
                                    @click="remove()"
                                    :disabled="loading"
                                    class="text-red-400 hover:text-red-600 transition disabled:opacity-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ringkasan & Checkout -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sticky bottom-20">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">Total Belanja</span>
                <span class="font-bold text-gray-900" x-text="'Rp ' + grandTotal()"></span>
            </div>
            <a href="/checkout"
                class="block w-full py-3 bg-orange-600 text-white rounded-xl font-semibold text-sm text-center hover:bg-orange-700 active:bg-orange-800 transition">
                Lanjut ke Checkout
            </a>
        </div>

    <?php endif; ?>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const jsonHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
};

async function jsonFetch(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: jsonHeaders,
        body: JSON.stringify(body),
    });

    const text = await res.text();
    try {
        return { ok: res.ok, status: res.status, data: JSON.parse(text) };
    } catch {
        console.error('Non-JSON response:', text);
        return { ok: false, status: res.status, data: null };
    }
}

// Registry semua cartItem yang aktif di halaman
// key: variantId, value: { qty, price }
const cartRegistry = {};

function dispatchCartChange() {
    window.dispatchEvent(new CustomEvent('cart-changed'));
}

function cartPage() {
    return {
        total: 0,

        init() {
            this.$nextTick(() => this.recalc());
            window.addEventListener('cart-changed', () => this.recalc());
        },

        recalc() {
            let sum = 0;
            for (const { qty, price } of Object.values(cartRegistry)) {
                sum += qty * price;
            }
            this.total = sum;
        },

        grandTotal() {
            return this.total.toLocaleString('id-ID');
        },
    }
}

function cartItem(variantId, initialQty, price) {
    return {
        variantId,
        qty: initialQty,
        price,
        loading: false,

        init() {
            cartRegistry[this.variantId] = { qty: this.qty, price: this.price };
            dispatchCartChange();
        },

        subtotal() {
            return (this.qty * this.price).toLocaleString('id-ID');
        },

        async increase() {
            await this.updateQty(this.qty + 1);
        },

        async decrease() {
            if (this.qty <= 1) {
                await this.remove();
                return;
            }
            await this.updateQty(this.qty - 1);
        },

        async updateQty(newQty) {
            this.loading = true;
            try {
                const { data } = await jsonFetch('/cart/update', {
                    variant_id: this.variantId,
                    quantity: newQty,
                });

                if (data && data.success) {
                    this.qty = newQty;
                    cartRegistry[this.variantId] = { qty: this.qty, price: this.price };
                    dispatchCartChange();

                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: { count: data.count }
                    }));
                } else {
                    alert(data?.message || 'Stok tidak cukup.');
                }
            } catch (e) {
                console.error('updateQty error:', e);
            } finally {
                this.loading = false;
            }
        },

        async remove() {
            this.loading = true;
            try {
                const { ok, data } = await jsonFetch('/cart/remove', {
                    variant_id: this.variantId,
                });

                if (ok) {
                    delete cartRegistry[this.variantId];
                    dispatchCartChange();
                    document.getElementById('cart-item-' + this.variantId)?.remove();

                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: { count: data?.count ?? null }
                    }));
                }
            } catch (e) {
                console.error('remove error:', e);
            } finally {
                this.loading = false;
            }
        },
    }
}
</script>

<?php $this->endSection() ?>