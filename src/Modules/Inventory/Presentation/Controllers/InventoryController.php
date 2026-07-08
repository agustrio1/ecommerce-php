<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Inventory\Application\Services\InventoryService;

class InventoryController
{
    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService();
    }

    public function index(Request $request): Response
    {
        $page   = max(1, (int) $request->query('page', 1));
        $search = (string) $request->query('search', '');
        $lowStockOnly = $request->query('low_stock') === '1';

        $result = $this->inventoryService->getAllVariants($page, 20, [
            'search'    => $search,
            'low_stock' => $lowStockOnly,
        ]);

        return Response::make(view('Inventory::index', [
            'title'        => 'Inventori',
            'variants'     => $result['data'],
            'total'        => $result['total'],
            'page'         => $page,
            'perPage'      => 20,
            'search'       => $search,
            'lowStockOnly' => $lowStockOnly,
            'lowStockCount' => $this->inventoryService->countLowStock(),
        ]));
    }

    public function history(Request $request, string $variantId): Response
    {
        $page = max(1, (int) $request->query('page', 1));

        $movements = $this->inventoryService->getMovementHistory((int) $variantId, $page);

        if ($request->isHtmx()) {
            return Response::make(view('Inventory::history-partial', [
                'movements' => $movements,
                'variantId' => $variantId,
            ]));
        }

        return Response::make(view('Inventory::history', [
            'title'     => 'Riwayat Stok',
            'movements' => $movements,
            'variantId' => $variantId,
        ]));
    }

    public function restock(Request $request, string $variantId): Response
    {
        $quantity = (int) $request->input('quantity');
        $note     = $request->input('note');
        $userId   = CurrentUserService::user()?->id;

        try {
            $this->inventoryService->restock((int) $variantId, $quantity, $userId, $note);

            if ($request->isHtmx()) {
                return Response::make('', 200)
                    ->withHeader('HX-Trigger', 'stockUpdated');
            }

            Session::flash('success', 'Stok berhasil ditambahkan.');
        } catch (ValidationException $e) {
            if ($request->isHtmx()) {
                return Response::json(['errors' => $e->errors()], 422);
            }
            Session::flash('error', implode(' ', $e->errors()));
        }

        return Response::redirect($request->header('Referer', '/admin/inventory'));
    }

    public function adjust(Request $request, string $variantId): Response
    {
        $newStock = (int) $request->input('stock');
        $note     = $request->input('note');
        $userId   = CurrentUserService::user()?->id;

        try {
            $this->inventoryService->adjustStock((int) $variantId, $newStock, $userId, $note);

            if ($request->isHtmx()) {
                return Response::make((string) $newStock, 200);
            }

            Session::flash('success', 'Stok berhasil disesuaikan.');
        } catch (ValidationException $e) {
            if ($request->isHtmx()) {
                return Response::json(['errors' => $e->errors()], 422);
            }
            Session::flash('error', implode(' ', $e->errors()));
        }

        return Response::redirect($request->header('Referer', '/admin/inventory'));
    }
}