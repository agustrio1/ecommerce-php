<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Services;

use App\Modules\Product\Infrastructure\Persistence\MysqlProductRepository;
use RuntimeException;

class ProductImageService
{
    private MysqlProductRepository $products;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    public function __construct()
    {
        $this->products = new MysqlProductRepository();
    }

    /**
     * Proses upload multi-file dari input <input type="file" name="images[]" multiple>.
     * $files format sesuai struktur $_FILES PHP untuk input array.
     */
    public function handleUploads(?array $files, int $productId): void
    {
        if ($files === null || empty($files['name'][0])) {
            return;
        }

        // Folder fisik tempat file disimpan
        $uploadDir = base_path('storage/uploads/products');

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $existingImages = $this->products->getImagesRaw($productId);
        $isFirstImage   = empty($existingImages);
        $count          = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $this->validateFile($files['type'][$i], $files['size'][$i]);

            $extension   = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $filename    = uniqid('prod_', true) . '.' . $extension;
            $destination = $uploadDir . '/' . $filename;

            if (! move_uploaded_file($files['tmp_name'][$i], $destination)) {
                throw new \RuntimeException("Gagal menyimpan file: {$files['name'][$i]}");
            }

            // Path yang disimpan ke DB: relatif dari storage/uploads/
            // Sehingga URL-nya: /storage/products/filename.jpg
            $this->products->addImage([
                'product_id' => $productId,
                'path'       => 'products/' . $filename,
                'alt_text'   => null,
                'is_primary' => ($isFirstImage && $i === 0) ? 1 : 0,
                'sort_order' => count($existingImages) + $i,
            ]);
        }
    }

    public function delete(int $imageId): void
    {
        // Catatan: untuk produksi, sebaiknya repository punya method findImageById()
        // supaya kita bisa hapus file fisik juga. Untuk sekarang, hapus record saja.
        $this->products->deleteImage($imageId);
    }

    private function validateFile(string $mimeType, int $size): void
    {
        if (! in_array($mimeType, self::ALLOWED_MIME, true)) {
            throw new RuntimeException('Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.');
        }

        if ($size > self::MAX_SIZE_BYTES) {
            throw new RuntimeException('Ukuran gambar maksimal 2MB.');
        }
    }
}