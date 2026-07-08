<?php $this->layout('layouts.storefront', ['title' => 'Tulis Ulasan']) ?>

<?php $this->section('content') ?>

<div class="py-4" x-data="reviewForm()">
    <div class="flex items-center gap-2 mb-4">
        <a href="/ulasan" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-bold text-gray-900 text-lg">Tulis Ulasan</h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <form method="POST" action="/ulasan" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="order_item_id" value="<?= $orderItemId ?>">

            <!-- Rating bintang -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex gap-1">
                    <template x-for="i in 5" :key="i">
                        <button type="button" @click="rating = i" class="text-3xl transition">
                            <span :class="i <= rating ? 'text-amber-400' : 'text-gray-200'">★</span>
                        </button>
                    </template>
                </div>
                <input type="hidden" name="rating" x-model="rating">
                <p class="text-xs text-gray-400 mt-1" x-text="ratingLabel()"></p>
            </div>

            <!-- Komentar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ulasan Anda</label>
                <textarea name="comment" rows="4" placeholder="Ceritakan pengalaman Anda dengan produk ini..."
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>

            <!-- Upload foto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tambah Foto (opsional, maks 5)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-orange-400 transition cursor-pointer"
                    @click="$refs.fileInput.click()"
                    @dragover.prevent @drop.prevent="handleDrop($event)">

                    <input type="file" name="images[]" multiple accept="image/*" x-ref="fileInput"
                        @change="handleFiles($event)" class="hidden">

                    <div x-show="previews.length === 0">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-xs text-gray-400">Klik untuk pilih foto</p>
                    </div>

                    <div x-show="previews.length > 0" @click.stop class="grid grid-cols-4 gap-2 cursor-default">
                        <template x-for="(src, i) in previews" :key="i">
                            <div class="relative aspect-square">
                                <img :src="src" class="w-full h-full object-cover rounded-lg">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <button type="submit"
                :disabled="rating === 0"
                class="w-full py-3 bg-orange-600 text-white rounded-xl font-semibold text-sm hover:bg-orange-700 transition disabled:opacity-40">
                Kirim Ulasan
            </button>
        </form>
    </div>
</div>

<script>
function reviewForm() {
    return {
        rating: 0,
        previews: [],

        ratingLabel() {
            const labels = { 1: 'Sangat Buruk', 2: 'Buruk', 3: 'Cukup', 4: 'Baik', 5: 'Sangat Baik' };
            return labels[this.rating] || 'Pilih rating';
        },

        handleFiles(event) {
            Array.from(event.target.files).slice(0, 5).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        handleDrop(event) {
            const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/')).slice(0, 5);
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;

            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(file);
            });
        }
    }
}
</script>

<?php $this->endSection() ?>