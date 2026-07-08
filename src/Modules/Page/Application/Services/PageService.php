<?php

declare(strict_types=1);

namespace App\Modules\Page\Application\Services;

use PDO;
use RuntimeException;

class PageService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, title, slug, is_published, sort_order, created_at FROM pages ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE slug = :slug AND is_published = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getPublished(): array
    {
        return $this->pdo->query('SELECT id, title, slug FROM pages WHERE is_published = 1 ORDER BY sort_order ASC')->fetchAll();
    }

    public function create(array $data): int
    {
        $slug = $this->generateSlug($data['title'], $data['slug'] ?? null);

        $stmt = $this->pdo->prepare(
            'INSERT INTO pages (title, slug, content, meta_title, meta_description, is_published, sort_order, created_at, updated_at)
             VALUES (:title, :slug, :content, :meta_title, :meta_description, :is_published, :sort_order, NOW(), NOW())'
        );
        $stmt->execute([
            'title'            => $data['title'],
            'slug'             => $slug,
            'content'          => $data['content'],
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_published'     => isset($data['is_published']) ? 1 : 0,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE pages SET title = :title, content = :content, meta_title = :meta_title,
             meta_description = :meta_description, is_published = :is_published,
             sort_order = :sort_order, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id'               => $id,
            'title'            => $data['title'],
            'content'          => $data['content'],
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_published'     => isset($data['is_published']) ? 1 : 0,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM pages WHERE id = :id')->execute(['id' => $id]);
    }

    private function generateSlug(string $title, ?string $custom = null): string
    {
        $slug = $custom ?: strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title), '-'));
        return $slug;
    }
}