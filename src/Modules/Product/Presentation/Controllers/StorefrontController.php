<?php

declare(strict_types=1);

namespace App\Modules\Product\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Category\Application\Services\CategoryService;
use App\Modules\Product\Application\Services\ProductService;

class StorefrontController
{
    private ProductService $productService;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->productService  = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function home(Request $request): Response
    {
        $featured      = $this->productService->paginate(1, 8, ['status' => 'published']);
        $categories    = $this->categoryService->findRootCategories();
        $images        = $this->productService->getPrimaryImagesByProducts($featured['data']);
        $flashSalePrices = $this->productService->getFlashSalePrices($featured['data']);

        return Response::make(view('storefront.home', [
            'title'           => config('app.name'),
            'products'        => $featured['data'],
            'categories'      => $categories,
            'productImages'   => $images,
            'flashSalePrices' => $flashSalePrices,
        ]));
    }

    /**
     * FIX SEO: filter kategori sebelumnya pakai ID mentah di URL
     * (?kategori=3) — sekarang pakai slug (?kategori=tas), lebih ramah
     * SEO dan lebih enak dibaca manusia, konsisten dengan pola
     * /kategori/{slug} yang sudah dipakai di categoryProducts().
     *
     * Resolve slug -> objek Category -> ambil ->id di sini (internal),
     * supaya ProductService::paginate() yang sudah mengharapkan filter
     * 'category_id' (int) tidak perlu diubah sama sekali.
     *
     * Fallback: kalau parameter kategori berupa angka murni (link lama
     * yang mungkin masih ter-bookmark/ter-index search engine sebelum
     * fix ini), tetap dicoba resolve lewat ID supaya link lama tidak
     * langsung mati — tapi link BARU yang dihasilkan template selalu
     * pakai slug.
     */
    public function products(Request $request): Response
    {
        $page      = max(1, (int) $request->query('page', 1));
        $search    = (string) $request->query('q', '');
        $catParam  = $request->query('kategori');
        $sort      = $request->query('sort', 'terbaru');
        $minPrice  = $request->query('harga_min');
        $maxPrice  = $request->query('harga_max');
        $minRating = $request->query('rating');

        $filters = ['status' => 'published'];
        if ($search !== '') $filters['search'] = $search;

        $activeCategory = null;
        if ($catParam) {
            try {
                if (ctype_digit((string) $catParam)) {
                    // Fallback untuk link lama berbasis ID.
                    $activeCategory = $this->categoryService->find((int) $catParam);
                } else {
                    $activeCategory = $this->categoryService->findBySlug((string) $catParam);
                }
                $filters['category_id'] = $activeCategory->id;
            } catch (\Throwable $e) {
                // Slug/ID tidak ditemukan — perlakukan sebagai "semua kategori"
                // daripada melempar error ke pengunjung.
                $activeCategory = null;
            }
        }

        if ($minPrice)  $filters['min_price']  = (float) $minPrice;
        if ($maxPrice)  $filters['max_price']  = (float) $maxPrice;
        if ($minRating) $filters['min_rating'] = (int) $minRating;
        if ($sort)      $filters['sort']       = $sort;

        $result          = $this->productService->paginate($page, 20, $filters);
        $categories      = $this->categoryService->findRootCategories();
        $images          = $this->productService->getPrimaryImagesByProducts($result['data']);
        $flashSalePrices = $this->productService->getFlashSalePrices($result['data']);

        return Response::make(view('storefront.products', [
            'title'              => $search ? "Hasil: {$search}" : 'Semua Produk',
            'products'           => $result['data'],
            'total'              => $result['total'],
            'page'               => $page,
            'perPage'            => 20,
            'search'             => $search,
            'categories'         => $categories,
            'activeCategorySlug' => $activeCategory?->slug,
            'activeCategory'     => $activeCategory,
            'sort'               => $sort,
            'productImages'      => $images,
            'flashSalePrices'    => $flashSalePrices,
            'minPrice'           => $minPrice,
            'maxPrice'           => $maxPrice,
            'minRating'          => $minRating,
        ]));
    }

    public function productDetail(Request $request, string $slug): Response
    {
        try {
            $product    = $this->productService->findBySlug($slug);
            $variants   = $this->productService->getVariants($product->id);
            $images     = $this->productService->getImages($product->id);
            $categories = $this->productService->getCategoryIds($product->id);

            $flashSaleService = new \App\Modules\FlashSale\Application\Services\FlashSaleService();
            $flashSalePrice   = $flashSaleService->getActivePriceForProduct($product->id);

            $metaTitle = $product->metaTitle
                ? $product->metaTitle . ' — ' . config('app.name')
                : $product->name . ' — ' . config('app.name');

            $metaDesc = $product->metaDescription
                ?: ($product->shortDescription
                    ?: 'Beli ' . $product->name . ' dengan harga terbaik di ' . config('app.name'));

            return Response::make(view('storefront.product-detail', [
                'title'            => $metaTitle,
                'meta_description' => $metaDesc,
                'meta_keywords'    => $product->metaKeywords ?: '',
                'og_image'         => ! empty($images) ? '/storage/' . $images[0]['path'] : null,
                'product'          => $product,
                'variants'         => $variants,
                'images'           => $images,
                'categories'       => $categories,
                'flashSalePrice'   => $flashSalePrice,
            ]));
        } catch (\RuntimeException $e) {
            return Response::notFound('Produk tidak ditemukan.');
        }
    }

    public function categoryProducts(Request $request, string $slug): Response
    {
        try {
            $category = $this->categoryService->findBySlug($slug);
        } catch (\Throwable $e) {
            return Response::notFound('Kategori tidak ditemukan.');
        }

        $page        = max(1, (int) $request->query('page', 1));
        $sort        = $request->query('sort', 'terbaru');
        $categoryIds = $this->getAllCategoryIds($category->id);

        $filters = ['status' => 'published', 'category_ids' => $categoryIds, 'sort' => $sort];

        $result          = $this->productService->paginate($page, 20, $filters);
        $images          = $this->productService->getPrimaryImagesByProducts($result['data']);
        $flashSalePrices = $this->productService->getFlashSalePrices($result['data']);
        $children        = $this->categoryService->findChildren($category->id);
        $breadcrumb      = $this->buildBreadcrumb($category);

        return Response::make(view('storefront.category-products', [
            'title'           => $category->name,
            'category'        => $category,
            'children'        => $children,
            'products'        => $result['data'],
            'total'           => $result['total'],
            'page'            => $page,
            'perPage'         => 20,
            'sort'            => $sort,
            'images'          => $images,
            'flashSalePrices' => $flashSalePrices,
            'breadcrumb'      => $breadcrumb,
        ]));
    }

    public function categories(Request $request): Response
    {
        $tree = $this->categoryService->getTree();

        $pdo    = db();
        $counts = [];
        $stmt   = $pdo->query(
            'SELECT pc.category_id, COUNT(*) AS total
             FROM product_categories pc
             JOIN products p ON p.id = pc.product_id
             WHERE p.deleted_at IS NULL AND p.status = "published"
             GROUP BY pc.category_id'
        );
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['category_id']] = (int) $row['total'];
        }

        return Response::make(view('storefront.categories', [
            'title'  => 'Semua Kategori',
            'tree'   => $tree,
            'counts' => $counts,
        ]));
    }

    public function liveSearch(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return Response::json(['products' => [], 'total' => 0]);
        }

        $result = $this->productService->paginate(1, 6, [
            'status' => 'published',
            'search' => $q,
        ]);

        $images = $this->productService->getPrimaryImagesByProducts($result['data']);

        $products = array_map(fn ($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'slug'  => $p->slug,
            'price' => number_format($p->price, 0, ',', '.'),
            'image' => isset($images[$p->id]) ? $images[$p->id] : null,
        ], $result['data']);

        return Response::json([
            'products' => $products,
            'total'    => $result['total'],
            'q'        => $q,
        ]);
    }

    // ===================== HELPERS =====================

    private function getAllCategoryIds(int $categoryId): array
    {
        $ids  = [$categoryId];
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE parent_id = :parent_id');
        $stmt->execute(['parent_id' => $categoryId]);

        foreach ($stmt->fetchAll() as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds((int) $child['id']));
        }

        return $ids;
    }

    private function buildBreadcrumb(object $category): array
    {
        $crumbs = [['name' => $category->name, 'slug' => $category->slug]];

        if ($category->parentId) {
            try {
                $parent  = $this->categoryService->find($category->parentId);
                $crumbs  = array_merge($this->buildBreadcrumb($parent), $crumbs);
            } catch (\Throwable $e) {}
        }

        return $crumbs;
    }
}