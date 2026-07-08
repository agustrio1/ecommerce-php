<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Coupon\Application\Services\CouponService;

class CouponController
{
    private CouponService $couponService;

    public function __construct()
    {
        $this->couponService = new CouponService();
    }

    public function index(Request $request): Response
    {
        return Response::make(view('Coupon::admin.index', [
            'title'   => 'Kupon Diskon',
            'coupons' => $this->couponService->all(),
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::make(view('Coupon::admin.form', [
            'title'  => 'Tambah Kupon',
            'coupon' => null,
        ]));
    }

    public function store(Request $request): Response
    {
        try {
            $this->couponService->create($request->all());
            Session::flash('success', 'Kupon berhasil dibuat.');
            return Response::redirect('/admin/coupons');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return Response::redirect('/admin/coupons/create');
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $coupon = $this->couponService->find((int) $id);
        if (!$coupon) return Response::notFound('Kupon tidak ditemukan.');

        return Response::make(view('Coupon::admin.form', [
            'title'  => 'Edit Kupon',
            'coupon' => $coupon,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        try {
            $this->couponService->update((int) $id, $request->all());
            Session::flash('success', 'Kupon berhasil diperbarui.');
            return Response::redirect('/admin/coupons');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return Response::redirect("/admin/coupons/{$id}/edit");
        }
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->couponService->delete((int) $id);
        Session::flash('success', 'Kupon berhasil dihapus.');
        return Response::redirect('/admin/coupons');
    }

    /**
     * AJAX: Validasi kupon dari halaman checkout.
     */
    public function validate(Request $request): Response
    {
        $code     = (string) $request->input('code');
        $subtotal = (float) $request->input('subtotal', 0);
        $userId   = \App\Modules\Auth\Application\Services\CurrentUserService::user()?->id;

        try {
            $result = $this->couponService->apply($code, $subtotal, $userId);
            return Response::json([
                'success'     => true,
                'code'        => $result['code'],
                'discount'    => $result['discount'],
                'description' => $result['description'],
                'type'        => $result['type'],
                'value'       => $result['value'],
                'message'     => 'Kupon berhasil diterapkan! Diskon: Rp ' . number_format($result['discount'], 0, ',', '.'),
            ]);
        } catch (\App\Core\Exceptions\ValidationException $e) {
            return Response::json([
                'success' => false,
                'message' => implode(' ', $e->errors()),
            ], 422);
        }
    }
}