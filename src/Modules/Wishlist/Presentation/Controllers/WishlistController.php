<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Wishlist\Application\Services\WishlistService;

class WishlistController
{
    private WishlistService $wishlistService;

    public function __construct()
    {
        $this->wishlistService = new WishlistService();
    }

    public function index(Request $request): Response
    {
        $user = CurrentUserService::user();
        if (!$user) return Response::redirect('/login');

        $items = $this->wishlistService->getByUser($user->id);

        return Response::make(view('Wishlist::index', [
            'title' => 'Wishlist Saya',
            'items' => $items,
        ]));
    }

    public function toggle(Request $request): Response
    {
        $user = CurrentUserService::user();
        if (!$user) {
            return Response::json(['success' => false, 'message' => 'Login diperlukan.', 'redirect' => '/login'], 401);
        }

        $productId = (int) $request->input('product_id');

        if ($productId <= 0) {
            return Response::json([
                'success' => false,
                'message' => 'Product ID tidak valid.',
            ], 422);
        }

        try {
            $added = $this->wishlistService->toggle($user->id, $productId);

            return Response::json([
                'success' => true,
                'added'   => $added,
                'message' => $added ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.',
                'count'   => $this->wishlistService->countByUser($user->id),
            ]);
        } catch (\Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function remove(Request $request, string $productId): Response
    {
        $user = CurrentUserService::user();
        if (!$user) return Response::redirect('/login');

        $this->wishlistService->toggle($user->id, (int) $productId);

        \App\Core\Http\Session::flash('success', 'Produk dihapus dari wishlist.');
        return Response::redirect('/wishlist');
    }
    
    public function count(Request $request): Response
    {
        $user = CurrentUserService::user();
        if (!$user) return Response::json(['count' => 0]);

        return Response::json([
            'count' => $this->wishlistService->countByUser($user->id),
        ]);
    }
}