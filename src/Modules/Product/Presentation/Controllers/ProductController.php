<?php

declare(strict_types=1);

namespace App\Modules\Product\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Category\Application\Services\CategoryService;
use App\Modules\Product\Application\Services\AttributeService;
use App\Modules\Product\Application\Services\ProductImageService;
use App\Modules\Product\Application\Services\ProductService;
use RuntimeException;

class ProductController
{
    private ProductService $productService;
    private CategoryService $categoryService;
    private AttributeService $attributeService;
    private ProductImageService $imageService;

    public function __construct()
    {
        $this->productService   = new ProductService();
        $this->categoryService  = new CategoryService();
        $this->attributeService = new AttributeService();
        $this->imageService     = new ProductImageService();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->productService->paginate($page, 15, [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ]);

        // Ambil primary image untuk semua produk (anti N+1)
        $productImages = $this->productService->getPrimaryImagesByProducts($result['data']);

        return Response::make(view('Product::index', [
            'products'      => $result['data'],
            'total'         => $result['total'],
            'page'          => $page,
            'perPage'       => 15,
            'search'        => $request->query('search', ''),
            'statusFilter'  => $request->query('status', ''),
            'productImages' => $productImages,
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::make(view('Product::create', [
            'categories' => $this->categoryService->getAll(),
            'attributes' => $this->attributeService->getAllWithValues(),
        ]));
    }

    public function store(Request $request): Response
    {
        try {
            $product = $this->productService->create($this->extractProductData($request));

            $this->imageService->handleUploads($request->file('images'), $product->id);

            Session::flash('success', 'Produk berhasil ditambahkan.');

            return Response::redirect('/admin/products');
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $request->all());

            return Response::redirect('/admin/products/create');
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $product = $this->productService->find((int) $id);

        return Response::make(view('Product::edit', [
            'product'             => $product,
            'categories'          => $this->categoryService->getAll(),
            'selectedCategoryIds' => array_column(
                $this->productService->getCategoryIds($product->id),
                'id'
            ),
            'attributes' => $this->attributeService->getAllWithValues(),
            'variants'   => $this->productService->getVariants($product->id),
            'images'     => $this->productService->getImages($product->id),
            // Ambil dari object $product — bukan dari $request
            // $request di GET request kosong, data SEO ada di DB
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        try {
            $this->productService->update((int) $id, $this->extractProductData($request));

            if ($request->file('images')) {
                $this->imageService->handleUploads($request->file('images'), (int) $id);
            }

            Session::flash('success', 'Produk berhasil diperbarui.');
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $request->all());
        }

        return Response::redirect("/admin/products/{$id}/edit");
    }

    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->productService->delete((int) $id);
            Session::flash('success', 'Produk berhasil dihapus.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return Response::redirect('/admin/products');
    }

    public function updateVariantStock(Request $request, string $variantId): Response
    {
        $stock = (int) $request->input('stock');

        try {
            $this->productService->updateVariantStock((int) $variantId, $stock);

            if ($request->isHtmx()) {
                return Response::make((string) $stock);
            }

            Session::flash('success', 'Stok varian berhasil diperbarui.');
        } catch (ValidationException $e) {
            if ($request->isHtmx()) {
                return Response::json(['errors' => $e->errors()], 422);
            }

            Session::flash('error', implode(' ', $e->errors()));
        }

        return Response::redirect($request->header('Referer', '/admin/products'));
    }

    public function deleteImage(Request $request, string $imageId): Response
    {
        $this->imageService->delete((int) $imageId);

        if ($request->isHtmx()) {
            return Response::make('');
        }

        Session::flash('success', 'Gambar berhasil dihapus.');

        return Response::redirect($request->header('Referer', '/admin/products'));
    }

    public function bulkAction(Request $request): Response
    {
        $action = (string) $request->input('action');
        $ids    = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            Session::flash('error', 'Pilih minimal satu produk.');
            return Response::redirect('/admin/products');
        }

        $ids          = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo          = db();

        switch ($action) {
            case 'publish':
                $pdo->prepare("UPDATE products SET status = 'published', updated_at = NOW() WHERE id IN ({$placeholders})")
                    ->execute($ids);
                Session::flash('success', count($ids) . ' produk berhasil dipublikasikan.');
                break;

            case 'draft':
                $pdo->prepare("UPDATE products SET status = 'draft', updated_at = NOW() WHERE id IN ({$placeholders})")
                    ->execute($ids);
                Session::flash('success', count($ids) . ' produk dijadikan draft.');
                break;

            case 'delete':
                $pdo->prepare("UPDATE products SET deleted_at = NOW(), updated_at = NOW() WHERE id IN ({$placeholders})")
                    ->execute($ids);
                Session::flash('success', count($ids) . ' produk berhasil dihapus.');
                break;

            default:
                Session::flash('error', 'Aksi tidak valid.');
        }

        return Response::redirect('/admin/products');
    }

    // ===================== PRIVATE =====================

    /**
     * Ekstrak & normalisasi data form produk.
     * Dipakai oleh store() dan update() — satu sumber kebenaran.
     */
    private function extractProductData(Request $request): array
    {
        $variantMode = (string) $request->input('variant_mode', 'single');

        $selectedAttributeValues = [];

        if ($variantMode === 'combination') {
            $rawAttributes = $request->input('attributes', []);

            foreach ((array) $rawAttributes as $attributeId => $valueIds) {
                $valueIds = array_filter((array) $valueIds);

                if (! empty($valueIds)) {
                    $selectedAttributeValues[] = [
                        'attribute_id' => (int) $attributeId,
                        'value_ids'    => array_map('intval', $valueIds),
                    ];
                }
            }
        }

        return [
            'name'              => trim((string) $request->input('name')),
            'sku'               => trim((string) $request->input('sku')),
            'description'       => $request->input('description'),
            'short_description' => $request->input('short_description'),
            'price'             => $request->input('price'),
            'compare_price'     => $request->input('compare_price') ?: null,
            'cost_price'        => $request->input('cost_price') ?: null,
            'weight'            => $request->input('weight') ?: null,
            'length'            => (int) $request->input('length', 0),
            'width'             => (int) $request->input('width', 0),
            'height'            => (int) $request->input('height', 0),
            'status'            => $request->input('status', 'draft'),
            'category_ids'      => array_map('intval', (array) $request->input('category_ids', [])),
            'variant_mode'      => $variantMode,
            'stock'             => (int) $request->input('stock', 0),
            'selected_attribute_values' => $selectedAttributeValues,

            // SEO fields — diambil dari form input saat POST
            // Saat edit, nilai awal form diisi dari $product->metaTitle dst (di view)
            'meta_title'        => $request->input('meta_title') ?: null,
            'meta_description'  => $request->input('meta_description') ?: null,
            'meta_keywords'     => $request->input('meta_keywords') ?: null,
        ];
    }
}