<?php

declare(strict_types=1);

namespace App\Modules\Banner\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Banner\Application\Services\BannerService;

class BannerController
{
    private BannerService $bannerService;

    public function __construct()
    {
        $this->bannerService = new BannerService();
    }

    public function index(Request $request): Response
    {
        return Response::make(view('Banner::admin.index', [
            'title'   => 'Kelola Banner',
            'banners' => $this->bannerService->all(),
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::make(view('Banner::admin.form', [
            'title'  => 'Tambah Banner',
            'banner' => null,
        ]));
    }

    public function store(Request $request): Response
    {
        $data = [
            'title'       => trim((string) $request->input('title')),
            'subtitle'    => $request->input('subtitle'),
            'button_text' => $request->input('button_text'),
            'button_url'  => $request->input('button_url'),
            'bg_color'    => $request->input('bg_color', '#f97316'),
            'sort_order'  => $request->input('sort_order', 0),
            'is_active'   => $request->input('is_active'),
        ];

        $file = $request->file('image');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $path = $this->bannerService->uploadImage($file);
            if ($path) $data['image_path'] = $path;
        }

        $this->bannerService->create($data);

        Session::flash('success', 'Banner berhasil ditambahkan.');
        return Response::redirect('/admin/banners');
    }

    public function edit(Request $request, string $id): Response
    {
        $banner = $this->bannerService->find((int) $id);

        if (!$banner) {
            return Response::notFound('Banner tidak ditemukan.');
        }

        return Response::make(view('Banner::admin.form', [
            'title'  => 'Edit Banner',
            'banner' => $banner,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $banner = $this->bannerService->find((int) $id);
        if (!$banner) {
            return Response::notFound('Banner tidak ditemukan.');
        }

        $data = [
            'title'       => trim((string) $request->input('title')),
            'subtitle'    => $request->input('subtitle'),
            'button_text' => $request->input('button_text'),
            'button_url'  => $request->input('button_url'),
            'bg_color'    => $request->input('bg_color', '#f97316'),
            'sort_order'  => $request->input('sort_order', 0),
            'is_active'   => $request->input('is_active'),
            'image_path'  => $banner['image_path'], // keep existing
        ];

        $file = $request->file('image');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $path = $this->bannerService->uploadImage($file);
            if ($path) {
                // Hapus gambar lama
                if ($banner['image_path']) {
                    $old = base_path('storage/uploads/' . $banner['image_path']);
                    if (file_exists($old)) unlink($old);
                }
                $data['image_path'] = $path;
            }
        }

        $this->bannerService->update((int) $id, $data);

        Session::flash('success', 'Banner berhasil diperbarui.');
        return Response::redirect('/admin/banners');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->bannerService->delete((int) $id);
        Session::flash('success', 'Banner berhasil dihapus.');
        return Response::redirect('/admin/banners');
    }
}