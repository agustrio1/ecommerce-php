<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * StorageController
 *
 * Serve file dari storage/uploads/ ke browser.
 * Mirip storage:link di Laravel tapi lewat PHP (lebih portable di Termux/Android).
 *
 * Route: GET /storage/{path}
 * Contoh: /storage/products/prod_abc123.jpg
 *       → storage/uploads/products/prod_abc123.jpg
 */
class StorageController
{
    private const STORAGE_ROOT = 'uploads';

    private const ALLOWED_MIME = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'pdf'  => 'application/pdf',
    ];

    public function serve(Request $request, string $path): Response
    {
        // Sanitasi path — cegah directory traversal (../../etc/passwd dll)
        $path = ltrim($path, '/');
        $path = str_replace(['..', "\0"], '', $path);
        $path = preg_replace('#/+#', '/', $path);

        $fullPath = base_path('storage/' . self::STORAGE_ROOT . '/' . $path);

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            return Response::make('File tidak ditemukan.', 404);
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (! isset(self::ALLOWED_MIME[$extension])) {
            return Response::make('Tipe file tidak diizinkan.', 403);
        }

        $mimeType = self::ALLOWED_MIME[$extension];
        $fileSize = filesize($fullPath);
        $lastModified = filemtime($fullPath);
        $etag = md5($fullPath . $lastModified);

        // Cache header — browser cache 7 hari, revalidate dengan etag
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch === $etag) {
            return Response::make('', 304)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', 'public, max-age=604800');
        }

        $content = file_get_contents($fullPath);

        return Response::make($content, 200)
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', (string) $fileSize)
            ->withHeader('Cache-Control', 'public, max-age=604800')
            ->withHeader('ETag', $etag)
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    }
}