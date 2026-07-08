<?php

declare(strict_types=1);

namespace App\Modules\Review\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Review\Application\Services\ReviewService;

class ReviewController
{
    private ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    /**
     * Halaman daftar produk yang bisa direview (dari order completed).
     */
    public function index(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        $items = $this->reviewService->getReviewableItems($user->id);

        return Response::make(view('Review::create-list', [
            'title' => 'Beri Ulasan',
            'items' => $items,
        ]));
    }

    /**
     * Form ulasan untuk order_item tertentu.
     */
    public function create(Request $request, string $orderItemId): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        return Response::make(view('Review::create', [
            'title'       => 'Tulis Ulasan',
            'orderItemId' => (int) $orderItemId,
        ]));
    }

    public function store(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        try {
            $this->reviewService->create(
                $user->id,
                (int) $request->input('order_item_id'),
                (int) $request->input('rating'),
                $request->input('comment'),
                $request->file('images')
            );

            Session::flash('success', 'Terima kasih atas ulasan Anda!');

            return Response::redirect('/ulasan');
        } catch (ValidationException $e) {
            Session::flash('error', implode(' ', $e->errors()));
            return Response::redirect($request->header('Referer', '/ulasan'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return Response::redirect($request->header('Referer', '/ulasan'));
        }
    }
}