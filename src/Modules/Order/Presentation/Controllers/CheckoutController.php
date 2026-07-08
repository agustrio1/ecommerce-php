<?php

declare(strict_types=1);

namespace App\Modules\Order\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Auth\Application\Services\CurrentUserService;
use App\Modules\Cart\Application\Services\CartService;
use App\Modules\Cart\Infrastructure\Persistence\MysqlAddressRepository;
use App\Modules\Order\Application\Services\OrderService;
use App\Modules\Payment\Application\Services\PaymentService;
use App\Modules\Setting\Application\Services\SettingService;
use App\Modules\Shipping\Infrastructure\Http\BiteshipClient;

class CheckoutController
{
    private CartService $cartService;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private MysqlAddressRepository $addressRepo;

    /**
     * TTL cache hasil cek ongkir (detik). Kalau user klik "Cek Ongkir"
     * berkali-kali untuk kombinasi alamat+isi cart yang sama dalam rentang
     * ini, kita pakai hasil yang sudah tersimpan daripada nembak ulang API
     * Biteship — mempercepat response tanpa perlu tunggu round-trip lagi.
     */
    private const RATES_CACHE_TTL = 120;

    /**
     * Rate yang sudah pernah dicek dianggap "valid untuk dipakai submit"
     * selama rentang ini. Setelah lewat, user wajib cek ongkir ulang
     * sebelum bisa checkout — mencegah pemakaian rate basi/kadaluarsa.
     */
    private const RATES_SESSION_TTL = 900; // 15 menit

    public function __construct()
    {
        $this->cartService    = new CartService();
        $this->orderService   = new OrderService();
        $this->paymentService = new PaymentService();
        $this->addressRepo    = new MysqlAddressRepository();
    }

    public function index(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            Session::flash('error', 'Silakan login untuk melanjutkan checkout.');
            return Response::redirect('/login');
        }

        if ($this->cartService->isEmpty()) {
            return Response::redirect('/cart');
        }

        $addresses  = $this->addressRepo->findByUser($user->id);
        $items      = $this->cartService->getItems();
        $subtotal   = $this->cartService->subtotal();
        $payMethods = $this->paymentService->getAvailableMethods();

        return Response::make(view('storefront.checkout', [
            'title'      => 'Checkout',
            'user'       => $user,
            'addresses'  => $addresses,
            'items'      => $items,
            'subtotal'   => $subtotal,
            'payMethods' => $payMethods,
        ]));
    }

    public function storeAddress(Request $request): Response
    {
        $user = CurrentUserService::user();
        if (! $user) {
            return Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $get  = fn($key, $default = '') => $body[$key] ?? $request->input($key, $default);

        try {
            $recipientName = trim((string) $get('recipient_name'));
            $phone         = trim((string) $get('phone'));
            $address       = trim((string) $get('address'));
            $areaId        = trim((string) $get('area_id'));

            if (! $recipientName) {
                return Response::json(['success' => false, 'message' => 'Nama penerima wajib diisi.'], 422);
            }
            if (! $phone) {
                return Response::json(['success' => false, 'message' => 'Nomor HP wajib diisi.'], 422);
            }
            if (! $address) {
                return Response::json(['success' => false, 'message' => 'Alamat lengkap wajib diisi.'], 422);
            }

            $created = $this->addressRepo->create([
                'user_id'        => $user->id,
                'label'          => $get('label', 'Rumah'),
                'recipient_name' => $recipientName,
                'phone'          => $phone,
                'address'        => $address,
                'province'       => $get('province'),
                'city'           => $get('city'),
                'district'       => $get('district'),
                'postal_code'    => trim((string) $get('postal_code')),
                'area_id'        => $areaId ?: null,
                'latitude'       => $get('latitude') ?: null,
                'longitude'      => $get('longitude') ?: null,
                'is_primary'     => $get('is_primary') ? 1 : 0,
            ]);

            return Response::json(['success' => true, 'address' => $created->toArray()]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateAddress(Request $request): Response
    {
        $user = CurrentUserService::user();
        if (! $user) {
            return Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int) ($body['id'] ?? 0);

        if (! $id) {
            return Response::json(['success' => false, 'message' => 'ID alamat tidak valid.'], 422);
        }

        $existing = $this->addressRepo->findById($id, $user->id);
        if (! $existing) {
            return Response::json(['success' => false, 'message' => 'Alamat tidak ditemukan.'], 404);
        }

        $recipientName = trim((string) ($body['recipient_name'] ?? $existing->recipientName));
        $phone         = trim((string) ($body['phone'] ?? $existing->phone));
        $address       = trim((string) ($body['address'] ?? $existing->address));
        $areaId        = trim((string) ($body['area_id'] ?? $existing->areaId ?? ''));

        if (! $recipientName) {
            return Response::json(['success' => false, 'message' => 'Nama penerima wajib diisi.'], 422);
        }
        if (! $phone) {
            return Response::json(['success' => false, 'message' => 'Nomor HP wajib diisi.'], 422);
        }
        if (! $address) {
            return Response::json(['success' => false, 'message' => 'Alamat lengkap wajib diisi.'], 422);
        }

        try {
            $this->addressRepo->update($id, $user->id, [
                'label'          => $body['label']       ?? $existing->label,
                'recipient_name' => $recipientName,
                'phone'          => $phone,
                'address'        => $address,
                'province'       => $body['province']    ?? $existing->province,
                'city'           => $body['city']        ?? $existing->city,
                'district'       => $body['district']    ?? $existing->district,
                'postal_code'    => $body['postal_code'] ?? $existing->postalCode,
                'area_id'        => $areaId ?: $existing->areaId,
                'latitude'       => $body['latitude']    ?? $existing->latitude,
                'longitude'      => $body['longitude']   ?? $existing->longitude,
                'is_primary'     => isset($body['is_primary']) ? (int) $body['is_primary'] : ($existing->isPrimary ? 1 : 0),
            ]);

            return Response::json(['success' => true]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getRates(Request $request): Response
    {
        $user = CurrentUserService::user();

        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $addressId = (int) ($body['address_id'] ?? $request->input('address_id', 0));

        if (! $user || ! $addressId) {
            return Response::make(
                '<p class="text-red-600 text-sm p-3 bg-red-50 rounded-lg">Pilih alamat terlebih dahulu.</p>'
            );
        }

        $address = $this->addressRepo->findById($addressId, $user->id);

        if (! $address) {
            return Response::make(
                '<p class="text-red-600 text-sm p-3 bg-red-50 rounded-lg">Alamat tidak ditemukan.</p>'
            );
        }

        if (! $address->areaId) {
            return Response::make(
                '<p class="text-amber-600 text-sm p-3 bg-amber-50 rounded-lg border border-amber-200">Alamat ini belum punya Area ID Biteship. Klik "Edit alamat ini" dan pilih kecamatan/kota.</p>'
            );
        }

        try {
            $items        = $this->cartService->toBiteshipItems();
            $originAreaId = SettingService::getInstance()->biteshipOriginAreaId();

            if (! $originAreaId) {
                return Response::make(
                    '<p class="text-red-600 text-sm p-3 bg-red-50 rounded-lg">Area ID toko belum diset. Hubungi admin.</p>'
                );
            }

            $cacheKey = $this->ratesCacheKey($originAreaId, $address->areaId, $items);
            $rates    = $this->readRatesCache($cacheKey);

            if ($rates === null) {
                $biteship = new BiteshipClient();
                $rates    = $biteship->getRatesByAreaId($originAreaId, $address->areaId, $items);
                $this->writeRatesCache($cacheKey, $rates);
            }

            // PENTING (keamanan): simpan rate yang BENAR-BENAR didapat dari
            // Biteship ke session, keyed per address_id. Saat submit() nanti,
            // pilihan kurir & harga yang dipakai untuk membuat order WAJIB
            // dicocokkan ke daftar ini — bukan dipercaya mentah-mentah dari
            // hidden input form. Ini mencegah user mengubah shipping_cost
            // lewat DevTools sebelum submit.
            $this->storeTrustedRates($addressId, $rates);

            return Response::make(view('storefront.checkout-partials.shipping-rates', [
                'rates'   => $rates,
                'address' => $address,
            ]));
        } catch (\Throwable $e) {
            return Response::make(
                '<p class="text-red-600 text-sm p-3 bg-red-50 rounded-lg">' . e($e->getMessage()) . '</p>'
            );
        }
    }

    public function submit(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        // PENTING (mencegah double-submit / duplicate order): kalau ada
        // proses submit() yang masih berjalan untuk session ini dalam 10
        // detik terakhir (misal user klik tombol "Bayar" 2x cepat, atau
        // koneksi lambat lalu user retry), tolak request kedua. Tanpa ini,
        // dua request submit() yang datang hampir bersamaan bisa sama-sama
        // lolos cek isEmpty() sebelum salah satunya sempat clearCart(),
        // menghasilkan 2 order dari cart yang sama.
        $lockKey = 'checkout_submit_lock';
        $lastSubmit = Session::get($lockKey);
        if ($lastSubmit !== null && (time() - $lastSubmit) < 10) {
            Session::flash('error', 'Permintaan checkout sebelumnya sedang diproses. Mohon tunggu sebentar.');
            return Response::redirect('/checkout');
        }
        Session::put($lockKey, time());

        if ($this->cartService->isEmpty()) {
            Session::forget($lockKey);
            return Response::redirect('/cart');
        }

        $addressId       = (int) $request->input('address_id');
        $courierCompany  = (string) $request->input('courier_company');
        $courierType     = (string) $request->input('courier_type');
        $notes           = $request->input('notes');
        $couponCode      = trim((string) $request->input('coupon_code', ''));

        if (! $addressId || ! $courierCompany || ! $courierType) {
            Session::forget($lockKey);
            Session::flash('error', 'Lengkapi alamat dan pilihan kurir sebelum checkout.');
            return Response::redirect('/checkout');
        }

        $address = $this->addressRepo->findById($addressId, $user->id);

        if (! $address) {
            Session::forget($lockKey);
            Session::flash('error', 'Alamat tidak ditemukan.');
            return Response::redirect('/checkout');
        }

        // Shipping cost diambil dari rate tervalidasi di session (bukan dari
        // input client) — lihat penjelasan di respons sebelumnya.
        $matchedRate = $this->findTrustedRate($addressId, $courierCompany, $courierType);

        if ($matchedRate === null) {
            Session::forget($lockKey);
            Session::flash('error', 'Data ongkir sudah kadaluarsa atau tidak valid. Silakan klik "Cek Ongkir" ulang lalu pilih kurir kembali.');
            return Response::redirect('/checkout');
        }

        $shippingCost = (float) $matchedRate['price'];
        $courierName  = $matchedRate['courier_name'] . ' ' . $matchedRate['courier_service_name'];

        // Validasi kupon (feedback awal). Pengecekan final yang benar-benar
        // atomic terjadi nanti lewat incrementUsage() SETELAH order dibuat.
        $validatedDiscount = 0.0;
        $validCouponCode   = null;

        if ($couponCode !== '') {
            try {
                $couponService     = new \App\Modules\Coupon\Application\Services\CouponService();
                $couponResult      = $couponService->apply($couponCode, $this->cartService->subtotal(), $user->id);
                $validatedDiscount = $couponResult['discount'];
                $validCouponCode   = $couponResult['code'];
            } catch (ValidationException $e) {
                Session::forget($lockKey);
                Session::flash('error', 'Kupon tidak valid: ' . implode(' ', $e->errors()));
                return Response::redirect('/checkout');
            }
        }

        // STEP 1: Buat order di DB dulu.
        // Stok tiap item divalidasi ulang secara ATOMIC di dalam transaksi
        // ini lewat InventoryService::recordMovement() (row lock + throw
        // kalau stok tidak cukup) — lihat OrderService::createFromCart().
        // Kalau ada item yang stoknya sudah keburu habis (race dengan
        // checkout lain), transaksi ini rollback dan order TIDAK terbentuk.
        $order = null;
        try {
            $order = $this->orderService->createFromCart(
                $this->cartService,
                $address,
                $courierCompany,
                $courierType,
                $courierName,
                $shippingCost,
                $user->id,
                $notes,
                $validCouponCode,
                $validatedDiscount
            );
        } catch (ValidationException $e) {
            Session::forget($lockKey);
            Session::flash('error', implode(' ', $e->errors()));
            return Response::redirect('/checkout');
        } catch (\Throwable $e) {
            Session::forget($lockKey);
            Session::flash('error', 'Gagal membuat order: ' . $e->getMessage());
            return Response::redirect('/checkout');
        }

        $this->clearTrustedRates($addressId);

        // STEP 1.5: Increment usage kupon SECARA ATOMIC, setelah order
        // berhasil dibuat. Kalau ternyata gagal (limit sudah tercapai
        // duluan oleh request lain — race condition), order yang baru
        // dibuat ini DIBATALKAN lagi (stok dikembalikan otomatis lewat
        // cancelOrder()), supaya tidak ada order dengan diskon kupon yang
        // sebenarnya sudah melebihi kuota.
        if ($validCouponCode) {
            try {
                $couponService = new \App\Modules\Coupon\Application\Services\CouponService();
                $incremented   = $couponService->incrementUsage($validCouponCode);

                if (! $incremented) {
                    $this->orderService->cancelOrder(
                        (int) $order['id'],
                        'Kuota kupon habis saat pemrosesan checkout (race condition).'
                    );

                    Session::forget($lockKey);
                    Session::flash('error', 'Kupon baru saja kehabisan kuota. Silakan checkout ulang tanpa kupon ini.');
                    return Response::redirect('/checkout');
                }
            } catch (\Throwable $e) {
                // Kalau incrementUsage() sendiri error (bukan soal limit),
                // order tetap dilanjutkan — ini kasus non-fatal yang lebih
                // baik dibiarkan lolos daripada membatalkan order valid
                // karena masalah teknis di luar dugaan.
            }
        }

        // STEP 2: Buat payment via iPaymu.
        try {
            $payment = $this->paymentService->createRedirectPayment(
                $order,
                $user->name,
                $address->phone,
                $user->email
            );

            Session::forget($lockKey);

            if (! empty($payment['redirect_url'])) {
                return Response::redirect($payment['redirect_url']);
            }

            return Response::redirect('/orders/' . $order['order_number'] . '/payment?new=1');

        } catch (\Throwable $e) {
            Session::forget($lockKey);
            Session::flash('payment_error',
                'Pembayaran otomatis gagal (' . $e->getMessage() . '). '
                . 'Order kamu sudah tersimpan. Kamu bisa mencoba bayar lagi dari halaman ini.'
            );
            return Response::redirect('/orders/' . $order['order_number'] . '/payment?new=1');
        }
    }

    // ===================== RATE CACHE (performa) =====================

    private function ratesCacheKey(string $originAreaId, string $destinationAreaId, array $items): string
    {
        // Hash isi cart juga ikut jadi bagian key, supaya kalau cart
        // berubah (tambah/kurang produk), cache lama otomatis tidak
        // terpakai lagi (key beda -> cache miss -> fetch fresh).
        $itemsSignature = md5(json_encode($items));

        return md5($originAreaId . '|' . $destinationAreaId . '|' . $itemsSignature);
    }

    private function cacheDir(): string
    {
        $dir = base_path('storage/cache/biteship-rates');

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function readRatesCache(string $key): ?array
    {
        $path = $this->cacheDir() . '/' . $key . '.json';

        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['expires_at'], $decoded['data'])) {
            return null;
        }

        if ($decoded['expires_at'] < time()) {
            @unlink($path);
            return null;
        }

        return $decoded['data'];
    }

    private function writeRatesCache(string $key, array $rates): void
    {
        $path = $this->cacheDir() . '/' . $key . '.json';

        $payload = [
            'expires_at' => time() + self::RATES_CACHE_TTL,
            'data'       => $rates,
        ];

        @file_put_contents($path, json_encode($payload), LOCK_EX);
    }

    // ===================== TRUSTED RATES (keamanan) =====================

    /**
     * Simpan daftar rate yang benar-benar didapat dari Biteship ke session,
     * di-scope per address_id, dengan timestamp supaya bisa dicek
     * kadaluarsanya di submit().
     *
     * CATATAN: memakai Session::put()/Session::get() — sesuaikan dengan
     * nama method yang sebenarnya ada di class App\Core\Http\Session kalau
     * berbeda (misalnya Session::set()). Kalau method ini belum ada di
     * Session, tambahkan wrapper sederhana di sekitar $_SESSION.
     */
    private function storeTrustedRates(int $addressId, array $rates): void
    {
        $all = Session::get('checkout_trusted_rates', []) ?? [];

        $all[$addressId] = [
            'rates'      => $rates,
            'created_at' => time(),
        ];

        Session::put('checkout_trusted_rates', $all);
    }

    private function findTrustedRate(int $addressId, string $courierCompany, string $courierType): ?array
    {
        $all = Session::get('checkout_trusted_rates', []) ?? [];

        if (! isset($all[$addressId])) {
            return null;
        }

        $entry = $all[$addressId];

        if ((time() - $entry['created_at']) > self::RATES_SESSION_TTL) {
            return null;
        }

        foreach ($entry['rates'] as $rate) {
            $rateCompany = $rate['courier_code'] ?? '';
            $rateType    = $rate['courier_service_code'] ?? ($rate['type'] ?? '');

            if ($rateCompany === $courierCompany && $rateType === $courierType) {
                return $rate;
            }
        }

        return null;
    }

    private function clearTrustedRates(int $addressId): void
    {
        $all = Session::get('checkout_trusted_rates', []) ?? [];
        unset($all[$addressId]);
        Session::put('checkout_trusted_rates', $all);
    }
}