<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Services;

use App\Core\Exceptions\ValidationException;
use App\Modules\Category\Domain\Entities\Category;
use App\Modules\Category\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\Category\Infrastructure\Persistence\MysqlCategoryRepository;
use RuntimeException;

class CategoryService
{
    private CategoryRepositoryInterface $categories;

    public function __construct()
    {
        $this->categories = new MysqlCategoryRepository();
    }

    public function getAll(): array
    {
        return $this->categories->all();
    }

    public function getTree(): array
    {
        $all = $this->categories->all();

        return $this->buildTree($all, null);
    }

    /**
     * @param Category[] $categories
     */
    private function buildTree(array $categories, ?int $parentId): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parentId === $parentId) {
                $children = $this->buildTree($categories, $category->id);

                $branch[] = [
                    'category' => $category,
                    'children' => $children,
                ];
            }
        }

        return $branch;
    }

    public function find(int $id): Category
    {
        $category = $this->categories->findById($id);

        if ($category === null) {
            throw new RuntimeException('Kategori tidak ditemukan.');
        }

        return $category;
    }

    public function create(array $data): Category
    {
        $errors = $this->validate($data);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }

        $slug = $this->generateUniqueSlug($data['name']);

        return $this->categories->create([
            'parent_id'   => $data['parent_id'] ?: null,
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'image'       => $data['image'] ?? null,
            'is_active'   => $data['is_active'] ?? 1,
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $existing = $this->find($id);

        $errors = $this->validate($data, $id);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }

        if ($data['parent_id'] && (int) $data['parent_id'] === $id) {
            throw new ValidationException(['parent_id' => 'Kategori tidak bisa menjadi induk dari dirinya sendiri.']);
        }

        $updateData = [
            'parent_id'   => $data['parent_id'] ?: null,
            'description' => $data['description'] ?? null,
            'image'       => $data['image'] ?? $existing->image,
            'is_active'   => $data['is_active'] ?? $existing->isActive,
            'sort_order'  => $data['sort_order'] ?? $existing->sortOrder,
        ];

        if ($data['name'] !== $existing->name) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        $this->categories->update($id, $updateData);
    }

    public function delete(int $id): void
    {
        $this->find($id);

        if ($this->categories->hasChildren($id)) {
            throw new RuntimeException('Kategori ini masih memiliki sub-kategori. Hapus atau pindahkan sub-kategori terlebih dahulu.');
        }

        if ($this->categories->hasProducts($id)) {
            throw new RuntimeException('Kategori ini masih digunakan oleh produk. Lepaskan produk dari kategori ini terlebih dahulu.');
        }

        $this->categories->delete($id);
    }
    
    public function findRootCategories(): array
    {
        return $this->categories->findRootCategories();
    }
    
    public function findBySlug(string $slug): object
    {
        $stmt = $this->categories->findBySlug($slug);
        if (!$stmt) {
            throw new \RuntimeException("Kategori dengan slug '{$slug}' tidak ditemukan.");
        }
        return $stmt;
    }

    public function findChildren(int $parentId): array
    {
        return $this->categories->findByParentId($parentId);
    }

    private function validate(array $data, ?int $exceptId = null): array
    {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors['name'] = 'Nama kategori maksimal 150 karakter.';
        }

        if (! empty($data['parent_id']) && $this->categories->findById((int) $data['parent_id']) === null) {
            $errors['parent_id'] = 'Kategori induk tidak valid.';
        }

        return $errors;
    }

    private function generateUniqueSlug(string $name, ?int $exceptId = null): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->categories->slugExists($slug, $exceptId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }
}