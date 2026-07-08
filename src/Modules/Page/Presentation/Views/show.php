<?php $this->layout('layouts.storefront', [
    'title'            => $title,
    'meta_description' => $meta_description ?? '',
]) ?>

<?php $this->section('content') ?>

<div class="py-6 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= e($page['title']) ?></h1>

    <div class="bg-white rounded-2xl border border-gray-100 p-6 prose-sm
                [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:mb-3 [&_h1]:mt-4
                [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mb-2 [&_h2]:mt-4
                [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mb-1 [&_h3]:mt-3
                [&_p]:mb-3 [&_p]:text-gray-700 [&_p]:leading-relaxed
                [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-3
                [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-3
                [&_a]:text-orange-600 [&_a]:underline
                [&_blockquote]:border-l-4 [&_blockquote]:border-orange-400 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-gray-500">
        <?= $page['content'] ?>
    </div>
</div>

<?php $this->endSection() ?>