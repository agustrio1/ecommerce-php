<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Cart\Application\Services\CartService;

class CartController
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function index(Request $request): Response
    {
        // getItems() sekarang otomatis sinkronkan harga flash sale terkini
        // sebelum dikembalikan (lihat CartService::getItems()).
        $items          = $this->cartService->getItems();
        $flashSaleInfo  = $this->cartService->getFlashSaleInfoForItems($items);
        $subtotal       = array_reduce($items, fn ($carry, $item) => $carry + $item->subtotal(), 0.0);

        return Response::make(view('storefront.cart', [
            'title'         => 'Keranjang Belanja',
            'items'         => $items,
            'flashSaleInfo' => $flashSaleInfo,
            'subtotal'      => $subtotal,
        ]));
    }

    public function add(Request $request): Response
    {
        $body      = $this->parseBody($request);
        $productId = (int) ($body['product_id'] ?? $request->input('product_id'));
        $variantId = (int) ($body['variant_id'] ?? $request->input('variant_id'));
        $quantity  = max(1, (int) ($body['quantity'] ?? $request->input('quantity', 1)));

        try {
            $this->cartService->addItem($productId, $variantId, $quantity);

            if ($request->isHtmx() || $request->wantsJson()) {
                return Response::json([
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan ke keranjang.',
                    'count'   => $this->cartService->countItems(),
                ]);
            }

            Session::flash('success', 'Produk berhasil ditambahkan ke keranjang.');
        } catch (ValidationException $e) {
            if ($request->isHtmx() || $request->wantsJson()) {
                return Response::json([
                    'success' => false,
                    'message' => implode(' ', $e->errors()),
                ], 422);
            }

            Session::flash('error', implode(' ', $e->errors()));
        }

        return Response::redirect($request->header('Referer', '/cart'));
    }

    public function update(Request $request): Response
    {
        $body      = $this->parseBody($request);
        $variantId = (int) ($body['variant_id'] ?? $request->input('variant_id'));
        $quantity  = (int) ($body['quantity']   ?? $request->input('quantity'));

        try {
            $this->cartService->updateQuantity($variantId, $quantity);

            if ($request->isHtmx() || $request->wantsJson()) {
                return Response::json([
                    'success' => true,
                    'count'   => $this->cartService->countItems(),
                ]);
            }
        } catch (ValidationException $e) {
            if ($request->isHtmx() || $request->wantsJson()) {
                return Response::json([
                    'success' => false,
                    'message' => implode(' ', $e->errors()),
                ], 422);
            }

            Session::flash('error', implode(' ', $e->errors()));
        }

        return Response::redirect('/cart');
    }

    public function remove(Request $request): Response
    {
        $body      = $this->parseBody($request);
        $variantId = (int) ($body['variant_id'] ?? $request->input('variant_id'));

        $this->cartService->removeItem($variantId);

        if ($request->isHtmx()) {
            return Response::make('', 200);
        }

        if ($request->wantsJson()) {
            return Response::json([
                'success' => true,
                'count'   => $this->cartService->countItems(),
            ]);
        }

        Session::flash('success', 'Item dihapus dari keranjang.');

        return Response::redirect('/cart');
    }

    public function count(Request $request): Response
    {
        return Response::json(['count' => $this->cartService->countItems()]);
    }

    private function parseBody(Request $request): array
    {
        if (str_contains($request->header('Content-Type', ''), 'application/json')) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }

        return [];
    }
}