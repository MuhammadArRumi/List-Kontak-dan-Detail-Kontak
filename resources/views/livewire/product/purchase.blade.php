<?php
use Carbon\Carbon;
use App\Models\Promo;
use App\Models\Product;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use App\Services\CouponService;
use App\Models\Transaction\Rent;
use App\Services\MidtransService;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction\Payment;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction\RentItem;
use App\Models\Master\BranchSchedule;
use function Livewire\Volt\{computed, mount, state, rules};
state([
    'kupon_terpakai' => null,
    'kode_kupon' => null,
    'jumlah_bayar' => 0,
    'product' => null,
    'promo' => null,
    'variants' => null,
    'selectedBranch' => null,
    'selectedColor' => null,
    'selectedStorage' => null,
    'selectedVariant' => null,
    'quantity' => 1,
    'tanggal_ambil' => null,
    'jam_ambil' => null,
    'jumlah_hari' => 1,
    'total_price_item' => 0,
    'subtotal' => 0,
    'biaya_layanan' => 0, // Hitung 0.8% di calculateTotals
    'biaya_materai' => 10000, // Fixed
    'diskon' => 0,
    'deposit' => 0,
    'grandtotal' => 0,
    'catatan' => null,
]);

mount(function () {
    $this->product = Product::where('slug', product)->firstOrFail();
    $this->selectedBranch = Branch::where('slug', request()->branch)->firstOrFail();

    if (auth()->user()?->userAddress) {
        $this->deposit = auth()->user()->userAddress->city->name !== $this->selectedBranch->city->name ? 500000 : 0;
    }

    $this->variants = ProductBranch::with(['color', 'storage', 'branch'])
        ->where('product_id', $this->product->id)
        ->where('branch_id', $this->selectedBranch->id)
        ->where('is_publish', 1)
        ->get();

    if ($this->variants->count() > 0) {
        $this->selectedVariant = $this->variants->first();
        $this->selectedColor = $this->selectedVariant->color_id;
        $this->selectedStorage = $this->selectedVariant->storage_id;
    }

    $this->tanggal_ambil = now()->format('Y-m-d');
    $this->jam_ambil = now()->addHour()->format('H:i');
});

$totals = computed(function () {
    $totalPriceItem = $this->selectedVariant->rent_price * $this->quantity * $this->jumlah_hari;
    $subtotal = $totalPriceItem;
    $serviceFee = $subtotal * 0.008;

    $couponService = app(CouponService::class);
    $dummyRent = new Rent();
    $dummyRent->start_date = $this->tanggal_ambil;
    $dummyRent->deposit_amount = $this->deposit;
    $dummyRent->ematerai_fee = $this->biaya_materai;
    $dummyRent->items = collect([
        (object) [
            'subtotal' => $subtotal,
            'price' => $this->selectedVariant->rent_price,
            'quantity' => $this->quantity,
            'duration_days' => $this->jumlah_hari,
        ]
    ]);
    $dummyRent->user_id = auth()->id();
    $dummyRent->branch_id = $this->selectedBranch->id;

    $calculated = $couponService->calculateDiscount($dummyRent, $this->promo);

    return [
        'subtotal' => $subtotal,
        'biaya_layanan' => $calculated['biaya_layanan'],
        'biaya_materai' => $calculated['biaya_materai'],
        'diskon' => $calculated['diskon'],
        'deposit' => $calculated['deposit'],
        'grandtotal' => $calculated['grandtotal'],
        'total_days' => $calculated['total_days'],
    ];
});

$updateTotals = function () {
    $computedTotals = $this->totals;
    $this->subtotal = $computedTotals['subtotal'];
    $this->biaya_layanan = $computedTotals['biaya_layanan'];
    $this->biaya_materai = $computedTotals['biaya_materai'];
    $this->diskon = $computedTotals['diskon'];
    $this->deposit = $computedTotals['deposit'];
    $this->grandtotal = $computedTotals['grandtotal'];
    $this->jumlah_hari = $computedTotals['total_days'];
    $this->jumlah_bayar = $computedTotals['grandtotal'] / 2;
    // dd($this->grandtotal);
    $this->dispatch('grandtotal-updated');
};
mount(function () {
    $this->product = Product::where('slug', request()->slug)->firstOrFail();
    $this->selectedBranch = Branch::where('slug', request()->branch)
        ->where('st', 'a')
        ->firstOrFail();

    // Initialize deposit
    $this->deposit = 500000; // Default for unauthenticated or missing address

    if (auth()->check()) {
        if (auth()->user()->userAddress) {
            // Condition 1: Check if user has never rented
            $hasRented = Rent::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'confirmed', 'on_rent', 'completed'])
                ->exists();

            // Condition 2: Check if user's city matches the branch's city
            $isSameCity = auth()->user()->userAddress->city->name === $this->selectedBranch->city->name;

            // Set deposit to 0 only if both conditions are false
            if ($hasRented && $isSameCity) {
                $this->deposit = 0;
            }
        }
    }

    // Load product variants for this branch
    $this->variants = ProductBranch::with(['color', 'storage', 'branch'])
        ->where('product_id', $this->product->id)
        ->where('branch_id', $this->selectedBranch->id)
        ->where('is_publish', 1)
        ->get();

    // If there are variants, set the first as selected
    if ($this->variants->count() > 0) {
        $this->selectedVariant = $this->variants->first();
        $this->selectedColor = $this->selectedVariant->color_id;
        $this->selectedStorage = $this->selectedVariant->storage_id;
    }

    // Set default pickup date and time
    $this->tanggal_ambil = now()->format('Y-m-d');
    $this->jam_ambil = now()->addHour()->format('H:i');

    // Update totals
    $this->updateTotals();
});
$terapkanKupon = function () {
    $this->validate(['kode_kupon' => 'required|exists:promos,code']);
    $this->is_loading = true;

    try {
        $this->promo = Promo::where('code', $this->kode_kupon)->first();
        $couponService = app(CouponService::class);
        
        $dummyRent = new Rent([
            'branch_id' => $this->selectedBranch->id,
            'total_days' => $this->jumlah_hari,
            'user_id' => auth()->id(),
            'start_date' => $this->tanggal_ambil,
        ]);
        $dummyRent->items = collect([ (object) ['subtotal' => $this->subtotal, 'price' => $this->selectedVariant->rent_price, 'quantity' => $this->quantity] ]);
        $validation = $couponService->validateCoupon($this->promo, $dummyRent);

        if (!$validation['valid']) {
            $this->addError('kode_kupon', implode(' ', $validation['errors']));
            $this->is_loading = false;
            return;
        }

        $this->kupon_terpakai = $this->promo->code;
        $this->updateTotals(); // Update otomatis, termasuk jumlah_bayar
        $this->is_loading = false;
    } catch (\Exception $e) {
        Log::error('Terapkan kupon gagal', ['error' => $e->getMessage()]);
        $this->addError('kode_kupon', 'Gagal menerapkan kupon: ' . $e->getMessage());
        $this->is_loading = false;
    }
};

// Tambah fungsi resetKupon jika ada
$resetKupon = function () {
    $this->promo = null;
    $this->kupon_terpakai = null;
    $this->kode_kupon = null;
    $this->updateTotals(); // Update otomatis
};
// Get active promo
$getActivePromo = function () {
    $now = now();
    $dayOfWeek = $now->dayOfWeek;
    $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);

    return Promo::where('is_active', true)
        ->where('start_date', '<=', $now)
        ->where('end_date', '>=', $now)
        ->where(function ($query) use ($isWeekend) {
            $query->where('day_restriction', 'all')
                ->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
        })
        ->first();
};

// Computed properties for unique colors and storages
$colors = computed(function () {
    return $this->variants->unique('color_id')->filter(fn ($v) => $v->color);
});

$storages = computed(function () {
    return $this->variants->unique('storage_id')->filter(fn ($v) => $v->storage);
});

// When selectedColor or selectedStorage changes, update selectedVariant
$updatedSelectedColor = function () {
    $this->updateSelectedVariant();
};

$updatedSelectedStorage = function () {
    $this->updateSelectedVariant();
};

$updateSelectedVariant = function () {
    $variant = $this->variants
    ->when($this->selectedColor, function ($query) {
        return $query->where('color_id', $this->selectedColor);
    })
    ->when($this->selectedStorage, function ($query) {
        return $query->where('storage_id', $this->selectedStorage);
    })
    ->first();

    $this->selectedVariant = $variant;
};
$getProductImage = function ($variant) {
    if ($variant->color) {
        $colorImage = asset('storage/product/' . $this->product->slug . '-' . Str::slug($variant->color->value) . '.png');
        if ($this->checkImageExists($colorImage)) {
            return $colorImage;
        }
    }
    return $this->product->image;
};

$checkImageExists = function ($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200');
};

// Check promo applicability
$showPromo = computed(function () {
    if (!$this->promo || !$this->selectedVariant) {
        return false;
    }

    return $this->promo && (
        $this->promo->scope === 'all' || 
        ($this->promo->scope === 'products' && $this->promo->products->contains($this->product->id))
    ) && (
        $this->selectedVariant->sale_price >= ($this->promo->min_order_amount ?? 0) || 
        $this->selectedVariant->rent_price >= ($this->promo->min_order_amount ?? 0)
    ) && (
        $this->promo->max_uses === null || 
        $this->promo->max_uses > $this->promo->usages->count()
    ) && in_array($this->promo->type, ['percentage', 'fixed_amount']);
});

// Calculate discounted prices
$discountedRent = computed(function () {
    if (!$this->selectedVariant || !$this->selectedVariant->rent_price) {
        return 0;
    }
    if (!$this->showPromo) {
        return $this->selectedVariant->rent_price;
    }
    if ($this->promo->type === 'percentage') {
        return max(0, $this->selectedVariant->rent_price * (1 - ($this->promo->value / 100)));
    } 
    if ($this->promo->type === 'fixed_amount') {
        return max(0, $this->selectedVariant->rent_price - $this->promo->value);
    }
    return $this->selectedVariant->rent_price;
});

$discountedSale = computed(function () {
    if (!$this->selectedVariant || !$this->selectedVariant->sale_price) {
        return 0;
    }
    if (!$this->showPromo) {
        return $this->selectedVariant->sale_price;
    }
    if ($this->promo->type === 'percentage') {
        return max(0, $this->selectedVariant->sale_price * (1 - ($this->promo->value / 100)));
    } 
    if ($this->promo->type === 'fixed_amount') {
        return max(0, $this->selectedVariant->sale_price - $this->promo->value);
    }
    return $this->selectedVariant->sale_price;
});

// Branch operational hours
$branchHours = computed(function () {
    return BranchSchedule::where('branch_id', $this->selectedBranch->id)
        ->orderBy('day_of_week')
        ->get();
});

$set = function($type, $value) {
    $this->$type = $value;
    $this->dispatch('variant-selected');
};
$checkProductAvailability = function($productId, $startDate, $endDate) {
    // Cek stok total produk
    $product = ProductBranch::find($productId);
    $totalStock = $product ? 1 : 0;
    
    // Hitung produk yang sedang dipinjam pada rentang waktu tersebut
    $rentedCount = RentItem::join('rents', 'rent_items.rent_id', '=', 'rents.id')
        ->where('rent_items.product_branch_id', $productId)
        ->where(function($query) use ($startDate, $endDate) {
            $query->whereBetween('rents.start_date', [$startDate, $endDate])
                  ->orWhereBetween('rents.end_date', [$startDate, $endDate])
                  ->orWhere(function($q) use ($startDate, $endDate) {
                      $q->where('rents.start_date', '<=', $startDate)
                        ->where('rents.end_date', '>=', $endDate);
                  });
        })
        ->whereIn('rents.status', ['confirmed', 'on_rent']) // Status yang menggunakan stok
        ->count();
    
    return $totalStock - $rentedCount;
};
$validatePickupTime = function () {
    $pickupDateTime = Carbon::parse($this->tanggal_ambil . ' ' . $this->jam_ambil);
    if ($pickupDateTime->isPast()) {
        $this->dispatch('toast-info', message: 'Waktu pengambilan tidak boleh di masa lalu.');
        return;
    }
    $schedule = BranchSchedule::where('branch_id', $this->selectedBranch->id)
        ->where('day_of_week', $pickupDateTime->format('l'))
        ->where('is_open', true)
        ->first();

    if (!$schedule) {
        $this->dispatch('toast-info', message: 'Waktu pengambilan di luar jam operasional cabang.');
        return;
    }

    $pickupTime = $pickupDateTime->format('H:i');
    $openTime = $schedule->open_time;
    $endTime = $schedule->end_time;

    // Handle jadwal melewati tengah malam (contoh: 22:00 - 06:00)
    if ($endTime < $openTime) {
        if ($pickupTime >= $openTime || $pickupTime <= $endTime) {
            // Waktu valid (dalam rentang melewati tengah malam)
        } else {
            $this->dispatch('toast-info', message: 'Waktu pengambilan di luar jam operasional cabang.');
            return;
        }
    } 
    // Handle jadwal normal (tidak melewati tengah malam)
    else {
        if ($pickupTime < $openTime || $pickupTime > $endTime) {
            $this->dispatch('toast-info', message: 'Waktu pengambilan di luar jam operasional cabang.');
            return;
        }
    }
};
$sewa = function () {
    $myTransaction = Rent::where('user_id', auth()->id())->whereNotIn('status',['completed','cancelled'])->first();
    if($myTransaction) {
        $this->dispatch('toast-info', message: 'Anda masih memiliki transaksi yang belum selesai.');
        return;
    }
    // Validasi manual untuk jumlah_bayar
    $minimumAmount = $this->grandtotal * 0.5;
    if ($this->jumlah_bayar < $minimumAmount || $this->jumlah_bayar > $this->grandtotal) {
        $this->dispatch('toast-info', message: 'Jumlah pembayaran tidak valid (min 50%, max total).');
        return;
    }
    // Validasi lainnya tetap seperti semula
    $this->validate(
        [
            'kode_kupon' => ['nullable', 'string', 'max:255'],
            'jumlah_hari' => ['required', 'integer', 'min:1'],
            'tanggal_ambil' => ['required', 'date', 'after_or_equal:today'],
            'jam_ambil' => ['required', 'date_format:H:i'],
            'jumlah_bayar' => ['required', 'numeric'], // Hapus min/max disini
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]
    );

    if ($this->validatePickupTime()) {
        return;
    }
    if (!$this->selectedVariant->isAvailable($this->tanggal_ambil, Carbon::parse($this->tanggal_ambil)->addDays($this->jumlah_hari), $this->quantity)) {
        $this->dispatch('toast-info', message: 'Stok tidak tersedia untuk periode yang dipilih.');
        return;
    }

    DB::beginTransaction();
    try {
        $rent = Rent::create([
            'user_id' => auth()->id(),
            'branch_id' => $this->selectedBranch->id,
            'status' => 'pending',
            'start_date' => $this->tanggal_ambil,
            'end_date' => Carbon::parse($this->tanggal_ambil)->addDays($this->jumlah_hari),
            'pickup_time' => $this->jam_ambil,
            'deposit_amount' => $this->deposit,
            'ematerai_fee' => $this->biaya_materai,
            'total_amount' => $this->grandtotal,
            'notes' => $this->catatan,
        ]);

        RentItem::create([
            'rent_id' => $rent->id,
            'product_branch_id' => $this->selectedVariant->id,
            'quantity' => $this->quantity,
            'price' => $this->selectedVariant->rent_price,
            'duration_days' => $this->jumlah_hari,
            'subtotal' => $this->subtotal,
        ]);

        $couponService = app(CouponService::class);
        if ($this->kode_kupon) {
            $couponService->applyCoupon($rent, $this->kode_kupon);
        }

        $rent->calculateTotalPrice();
        $midtransService = app(MidtransService::class);
        $orderId = $rent->code;
        $snapToken = $midtransService->createSnapToken($rent, $this->jumlah_bayar, false);
        if (!$snapToken) {
            throw new Exception('Failed to generate snap token');
        }
        $serviceFee = $rent->items->sum('subtotal') * 0.8 / 100; // 0.8% service fee
        $paymentData = [
            'paid_amount' => $this->jumlah_bayar, // Default full payment
            'remaining_amount' => $rent->total_amount - $this->jumlah_bayar,
            'deposit_amount' => $rent->deposit_amount ?? 0,
            'service_fee' => $serviceFee, // 0.8% service fee
            'ematerai_fee' => 10000,
        ];
        $payment = Payment::castAndCreate([
            'payable_type' => get_class($rent),
            'payable_id' => $rent->id,
            'user_id' => Auth::id(),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID', 'default_merchant'),
            'order_id' => $midtransService->generateOrderId($rent),
            'gross_amount' => $rent->total_amount,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
            'transaction_time' => now(),
            'payment_data' => json_encode($paymentData),
            'snap_token' => $snapToken
        ]);
        DB::commit();

        $this->dispatch('show-snap', [
            'token' => $snapToken,
            'rentCode' => $rent->code
        ]);
        // $this->redirect(route('transaction.view', ['code' => $rent->code]));
    } catch (\App\Exceptions\InvalidPaymentAmountException $e) {
        $this->dispatch('toast-info', message: $e->getMessage());
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Rental creation failed', ['error' => $e->getMessage()]);
        $this->dispatch('toast-info', message: 'Gagal membuat sewa: ' . $e->getMessage());
    }
};
$decreaseHari = function() {
    $this->jumlah_hari = $this->jumlah_hari- 1;
    $this->updateTotals();
};
$increaseHari = function(){
    $this->jumlah_hari = $this->jumlah_hari + 1;
    $this->updateTotals();
};
?>