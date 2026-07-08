<?php

declare(strict_types=1);

namespace App\Modules\Category\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Category\Application\Services\CategoryService;
use RuntimeException;

class CategoryController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }

    public function index(Request $request): Response
    {
        $tree = $this->categoryService->getTree();

        return Response::make(view('Category::index', ['tree' => $tree]));
    }

    public function create(Request $request): Response
    {
        $categories = $this->categoryService->getAll();

        return Response::make(view('Category::create', ['categories' => $categories]));
    }

    public function store(Request $request): Response
    {
        try {
            $this->categoryService->create([
                'parent_id'   => $request->input('parent_id') ?: null,
                'name'        => trim((string) $request->input('name')),
                'description' => $request->input('description'),
                'is_active'   => $request->input('is_active') ? 1 : 0,
                'sort_order'  => (int) $request->input('sort_order', 0),
            ]);
        } catch (ValidationException $e) {
            return $this->backWithErrors($request, $e->errors());
        }

        Session::flash('success', 'Kategori berhasil ditambahkan.');

        return Response::redirect('/admin/categories');
    }

    public function edit(Request $request, string $id): Response
    {
        $category = $this->categoryService->find((int) $id);
        $categories = $this->categoryService->getAll();

        return Response::make(view('Category::edit', [
            'category'   => $category,
            'categories' => array_filter($categories, fn ($c) => $c->id !== $category->id),
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        try {
            $this->categoryService->update((int) $id, [
                'parent_id'   => $request->input('parent_id') ?: null,
                'name'        => trim((string) $request->input('name')),
                'description' => $request->input('description'),
                'is_active'   => $request->input('is_active') ? 1 : 0,
                'sort_order'  => (int) $request->input('sort_order', 0),
            ]);
        } catch (ValidationException $e) {
            return $this->backWithErrors($request, $e->errors());
        }

        Session::flash('success', 'Kategori berhasil diperbarui.');

        return Response::redirect('/admin/categories');
    }

    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->categoryService->delete((int) $id);
            Session::flash('success', 'Kategori berhasil dihapus.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return Response::redirect('/admin/categories');
    }

    private function backWithErrors(Request $request, array $errors): Response
    {
        if ($request->wantsJson() || $request->isHtmx()) {
            return Response::json(['errors' => $errors], 422);
        }

        Session::flash('errors', $errors);
        Session::flash('old', $request->all());

        return Response::redirect($request->header('Referer', '/admin/categories'));
    }
}