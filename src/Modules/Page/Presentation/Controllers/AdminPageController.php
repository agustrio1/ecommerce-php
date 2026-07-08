<?php

declare(strict_types=1);

namespace App\Modules\Page\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Page\Application\Services\PageService;

class AdminPageController
{
    private PageService $pageService;

    public function __construct()
    {
        $this->pageService = new PageService();
    }

    public function index(Request $request): Response
    {
        return Response::make(view('Page::admin.index', [
            'title' => 'Halaman Statis',
            'pages' => $this->pageService->all(),
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::make(view('Page::admin.form', [
            'title' => 'Buat Halaman',
            'page'  => null,
        ]));
    }

    public function store(Request $request): Response
    {
        $this->pageService->create($request->all());
        Session::flash('success', 'Halaman berhasil dibuat.');
        return Response::redirect('/admin/pages');
    }

    public function edit(Request $request, string $id): Response
    {
        $page = $this->pageService->find((int) $id);
        if (!$page) return Response::notFound('Halaman tidak ditemukan.');

        return Response::make(view('Page::admin.form', [
            'title' => 'Edit Halaman',
            'page'  => $page,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $this->pageService->update((int) $id, $request->all());
        Session::flash('success', 'Halaman berhasil diperbarui.');
        return Response::redirect('/admin/pages');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->pageService->delete((int) $id);
        Session::flash('success', 'Halaman berhasil dihapus.');
        return Response::redirect('/admin/pages');
    }
}