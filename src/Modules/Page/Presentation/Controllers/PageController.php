<?php

declare(strict_types=1);

namespace App\Modules\Page\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Page\Application\Services\PageService;

class PageController
{
    private PageService $pageService;

    public function __construct()
    {
        $this->pageService = new PageService();
    }

    public function show(Request $request, string $slug): Response
    {
        $page = $this->pageService->findBySlug($slug);

        if (!$page) {
            return Response::notFound('Halaman tidak ditemukan.');
        }

        return Response::make(view('Page::show', [
            'title'            => $page['meta_title'] ?: $page['title'],
            'meta_description' => $page['meta_description'],
            'page'             => $page,
        ]));
    }
}