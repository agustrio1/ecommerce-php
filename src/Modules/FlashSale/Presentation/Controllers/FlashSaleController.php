<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\FlashSale\Application\Services\FlashSaleService;
use App\Modules\Product\Application\Services\ProductService;

class FlashSaleController
{
    private FlashSaleService $flashSaleService;
    private ProductService $productService;

    public function __construct()
    {
        $this->flashSaleService = new FlashSaleService();
        $this->productService   = new ProductService();
    }

    public function index(Request $request): Response
    {
        return Response::make(view('FlashSale::admin.index', [
            'title'      => 'Flash Sale',
            'flashSales' => $this->flashSaleService->all(),
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::make(view('FlashSale::admin.form', [
            'title' => 'Buat Flash Sale',
        ]));
    }

    public function store(Request $request): Response
    {
        $id = $this->flashSaleService->create([
            'name'      => $request->input('name'),
            'starts_at' => $request->input('starts_at'),
            'ends_at'   => $request->input('ends_at'),
            'is_active' => $request->input('is_active'),
        ]);

        Session::flash('success', 'Flash sale berhasil dibuat. Tambahkan produk sekarang.');

        return Response::redirect("/admin/flash-sales/{$id}");
    }

    public function show(Request $request, string $id): Response
    {
        $flashSale = $this->flashSaleService->find((int) $id);
        if (!$flashSale) return Response::notFound('Flash sale tidak ditemukan.');

        $allProducts = $this->productService->paginate(1, 100, ['status' => 'published']);

        return Response::make(view('FlashSale::admin.show', [
            'title'      => 'Kelola Flash Sale',
            'flashSale'  => $flashSale,
            'allProducts' => $allProducts['data'],
        ]));
    }

    public function addProduct(Request $request, string $id): Response
    {
        $this->flashSaleService->addProduct(
            (int) $id,
            (int) $request->input('product_id'),
            (float) $request->input('sale_price'),
            $request->input('stock_limit') ? (int) $request->input('stock_limit') : null
        );

        Session::flash('success', 'Produk berhasil ditambahkan ke flash sale.');

        return Response::redirect("/admin/flash-sales/{$id}");
    }

    public function removeProduct(Request $request, string $id, string $productId): Response
    {
        $this->flashSaleService->removeProduct((int) $id, (int) $productId);
        Session::flash('success', 'Produk dihapus dari flash sale.');
        return Response::redirect("/admin/flash-sales/{$id}");
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->flashSaleService->delete((int) $id);
        Session::flash('success', 'Flash sale berhasil dihapus.');
        return Response::redirect('/admin/flash-sales');
    }
}