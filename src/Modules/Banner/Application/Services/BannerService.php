<?php

declare(strict_types=1);

namespace App\Modules\Banner\Application\Services;

use PDO;

class BannerService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function getActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM banners ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO banners (title, subtitle, image_path, button_text, button_url, bg_color, sort_order, is_active, created_at, updated_at)
             VALUES (:title, :subtitle, :image_path, :button_text, :button_url, :bg_color, :sort_order, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            'title'       => $data['title'],
            'subtitle'    => $data['subtitle'] ?? null,
            'image_path'  => $data['image_path'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_url'  => $data['button_url'] ?? null,
            'bg_color'    => $data['bg_color'] ?? '#f97316',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'is_active'   => isset($data['is_active']) ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE banners SET title = :title, subtitle = :subtitle, image_path = :image_path,
             button_text = :button_text, button_url = :button_url, bg_color = :bg_color,
             sort_order = :sort_order, is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id'          => $id,
            'title'       => $data['title'],
            'subtitle'    => $data['subtitle'] ?? null,
            'image_path'  => $data['image_path'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_url'  => $data['button_url'] ?? null,
            'bg_color'    => $data['bg_color'] ?? '#f97316',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'is_active'   => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        // Hapus file gambar
        $banner = $this->find($id);
        if ($banner && $banner['image_path']) {
            $path = base_path('storage/uploads/' . $banner['image_path']);
            if (file_exists($path)) unlink($path);
        }
        $this->pdo->prepare('DELETE FROM banners WHERE id = :id')->execute(['id' => $id]);
    }

    public function uploadImage(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes, true)) return null;
        if ($file['size'] > 3 * 1024 * 1024) return null;

        $uploadDir = base_path('storage/uploads/banners');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'banner_' . uniqid() . '.' . $ext;
        $dest     = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'banners/' . $filename;
        }
        return null;
    }
}