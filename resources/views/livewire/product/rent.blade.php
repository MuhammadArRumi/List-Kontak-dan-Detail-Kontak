<?php
use Carbon\Carbon;
use App\Models\ProductBranch;
use App\Services\CouponService;
use App\Models\Transaction\Rent;
use App\Services\MidtransService;
use App\Models\Transaction\Payment;
use App\Models\Transaction\RentItem;
use App\Models\Master\BranchSchedule;
use function Livewire\Volt\{computed, mount, state, rules};

state([
    'product',
    'branch',
]);

state([
    'kupon_terpakai' => null,
    'kode_kupon' => null,
    'jumlah_bayar' => 0,
    'product' => null,
    'promo' => null,
    'variants' => null,
    'branch' => null,
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

    $this->deposit = 500000; // Default for unauthenticated or missing address

    if (auth()->check()) {
        if (auth()->user()->userAddress) {
            // Condition 1: Check if user has never rented
            $hasRented = Rent::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'confirmed', 'on_rent', 'completed'])
                ->exists();

            // Condition 2: Check if user's city matches the branch's city
            $isSameCity = auth()->user()->userAddress->city->name === $this->branch->city->name;

            // Set deposit to 0 only if both conditions are false
            if ($hasRented && $isSameCity) {
                $this->deposit = 0;
            }
        }
    }

    $this->variants = ProductBranch::with(['color', 'storage', 'branch'])
        ->where('product_id', $this->product->id)
        ->where('branch_id', $this->branch->id)
        ->where('is_publish', 1)
        ->get();

    if ($this->variants->count() > 0) {
        $this->selectedVariant = $this->variants->first();
        $this->selectedColor = $this->selectedVariant->color_id;
        $this->selectedStorage = $this->selectedVariant->storage_id;
    }

    $this->tanggal_ambil = now()->format('Y-m-d');
    $this->jam_ambil = now()->addHour()->format('H:i');

    $this->updateTotals();
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
    $dummyRent->branch_id = $this->branch->id;

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
$terapkanKupon = function () {
    $this->validate(['kode_kupon' => 'required|exists:promos,code']);
    $this->is_loading = true;

    try {
        $this->promo = Promo::where('code', $this->kode_kupon)->first();
        $couponService = app(CouponService::class);
        
        $dummyRent = new Rent([
            'branch_id' => $this->branch->id,
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
    return BranchSchedule::where('branch_id', $this->branch->id)
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
    $schedule = BranchSchedule::where('branch_id', $this->branch->id)
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
            'branch_id' => $this->branch->id,
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
<div>
    <div class="card">
        <div class="card-body p-8">
            <div class="mb-4">
                <span class="badge bg-primary bg-opacity-10 text-primary fs-7 fw-semibold rounded-pill py-2">
                    {{ $this->product->category->name ?? 'No Category' }}
                </span>
            </div>
            <h1 class="mb-2 fs-2x fw-bold text-gray-900">{{ $this->product->name }}</h1>
            <div class="d-flex align-items-center text-muted fs-7 mb-6">
                <span class="me-3">
                    <i class="ki-solid ki-star text-warning me-1"></i>
                    {{ number_format($this->product->averageRating()) }}
                    @if($this->product->ratingsCount() > 0)
                        <span class="text-muted">({{ $this->product->ratingsCount() }} Ulasan)</span>
                    @endif
                </span>
                <span>
                    <i class="ki-filled ki-geolocation text-danger me-1"></i> 
                    {{ $this->branch->name }}
                </span>
            </div>
            @if($this->colors->count() > 1)
            <div class="mb-6">
                <label class="form-label fw-semibold d-block mb-3">Warna</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($this->colors as $variant)
                        @if($variant->color)
                        @php
                        $isActive = $this->selectedColor == $variant->color_id;
                        $isDisabled = $this->selectedStorage && !$this->variants->contains(fn($v) => 
                            $v->color_id == $variant->color_id && 
                            $v->storage_id == $this->selectedStorage
                        );
                        @endphp
                        <div 
                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $variant->color->value }}"
                            class="color-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}" 
                            style="background-color: {{ $variant->warna($variant->color->value) }};"
                            wire:click="set('selectedColor', {{ $variant->color_id }})"
                            aria-label="Pilih warna {{ $variant->color->value }}"
                            @if($isDisabled) aria-disabled="true" @endif
                        ></div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
            @if($this->storages->count() > 1)
            <div class="mb-8">
                <label class="form-label fw-semibold d-block mb-3">Storage</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($this->storages as $variant)
                        @php
                            $isActive = $this->selectedStorage == $variant->storage_id;
                            $isDisabled = $this->selectedColor && !$this->variants->contains(fn($v) => 
                                $v->storage_id == $variant->storage_id && 
                                $v->color_id == $this->selectedColor
                            );
                        @endphp
                        <span 
                            class="badge bg-light text-dark py-3 px-4 fs-6 fw-semibold storage-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                            wire:click="set('selectedStorage', {{ $variant->storage_id }})"
                            aria-label="Pilih storage {{ $variant->storage->value }}"
                            @if($isDisabled) aria-disabled="true" @endif
                        >
                            {{ $variant->storage->value }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="price-display mb-8">
                @if($this->selectedVariant && $this->selectedVariant->sale_price > 0)
                    <div class="rental-price mb-6">
                        <div class="d-flex align-items-baseline">
                            <span class="fs-2x fw-bold text-primary me-2">
                                Rp{{ number_format($this->discountedRent, 0, ',', '.') }}
                            </span>
                            <span class="text-muted fs-6">/hari</span>
                            
                            @if($this->showPromo)
                                <span class="text-muted fs-7 ms-2 text-decoration-line-through">
                                    Rp{{ number_format($this->selectedVariant->rent_price, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div class="text-muted fs-7 mt-1">Sewa per hari</div>
                    </div>
                    <div class="separator separator-content my-6">atau</div>
                    <div class="purchase-option bg-light-success p-4 rounded-3 mb-6">
                        <div class="d-flex flex-wrap align-items-center">
                            <span class="fs-6 me-2">Dapat dimiliki dengan:</span>
                            <span class="fs-2x fw-bold text-success me-2">
                                Rp{{ number_format($this->discountedSale, 0, ',', '.') }}
                            </span>
                            
                            @if($this->showPromo)
                                <span class="text-muted fs-7 text-decoration-line-through">
                                    Rp{{ number_format($this->selectedVariant->sale_price, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-success bg-opacity-10 text-success fs-7">
                                <i class="ki-filled ki-star me-1"></i>Hemat hingga {{ number_format(100 - ($this->selectedVariant->sale_price/$this->selectedVariant->rent_price)*100, 0) }}%
                            </span>
                        </div>
                    </div>
                @else
                    <div class="rental-price mb-6">
                        <div class="d-flex align-items-baseline">
                            <span class="fs-2x fw-bold text-primary me-2">
                                Rp{{ number_format($this->discountedRent, 0, ',', '.') }}
                            </span>
                            <span class="text-muted fs-6">/hari</span>
                            
                            @if($this->showPromo)
                                <span class="text-muted fs-7 ms-2 text-decoration-line-through">
                                    Rp{{ number_format($this->selectedVariant->rent_price, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div class="text-muted fs-7 mt-1">Sewa per hari</div>
                    </div>
                @endif
            </div>
            <div class="pickup-datetime mb-8">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold me-4">
                            Jumlah @if($this->selectedVariant->sale_price == 0) Hari @endif :
                            <span class="ms-1" data-bs-toggle="tooltip" aria-label="{{$this->selectedVariant->sale_price > 0 ? 'Jumlah = Jumlah hari sewa atau Quantity yang ingin di beli' : '' }}" data-bs-original-title="{{$this->selectedVariant->sale_price > 0 ? 'Jumlah = Jumlah hari sewa atau Quantity yang ingin di beli' : 'Jumlah hari kamu ingin sewa' }}">
                                <i class="ki-filled ki-information text-gray-500 fs-6"></i>
                            </span>
                        </label>
                        <div class="position-relative w-md-100px"
                            data-kt-dialer="true"
                            data-kt-dialer-min="1"
                            data-kt-dialer-max="365"
                            data-kt-dialer-step="1"
                            data-kt-dialer-prefix=""
                            data-kt-dialer-decimals="0">
                            <button type="button" wire:click="decreaseHari" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0" data-kt-dialer-control="decrease">
                                <i class="ki-filled ki-minus-squared fs-2"></i>
                            </button>
                            <input type="text" wire:model.live="jumlah_hari" class="form-control form-control-solid border-0 ps-12" data-kt-dialer-control="input" placeholder="Amount" readonly />
                            <button type="button" wire:click="increaseHari" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0" data-kt-dialer-control="increase">
                                <i class="ki-filled ki-plus-squared fs-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <x-form-group name="tanggal_ambil" label="Tanggal Ambil" required>
                            <x-form-input type="date" name="tanggal_ambil" min="{{ now()->format('Y-m-d') }}" class="bg-transparent" id="tanggal_ambil"/>
                        </x-form-group>
                    </div>
                    <div class="col-md-4">
                        <x-form-group name="jam_ambil" label="Jam Ambil" required>
                            <x-form-input type="time" name="jam_ambil" min="{{ now()->format('H:i') }}" class="bg-transparent" id="jam_ambil"/>
                        </x-form-group>
                    </div>
                    <div class="col-md-12">
                        <x-form-group name="catatan" label="Catatan">
                            <x-form-textarea name="catatan" class="bg-transparent" id="catatan"/>
                        </x-form-group>
                    </div>
                </div>
            </div>
            @if(!$kupon_terpakai)
            <div class="mb-4">
                <label for="kode_kupon" class="form-label">Gunakan Kupon:</label>
                <div class="input-group">
                    <x-form-input name="kode_kupon" class="bg-transparent" autofocus placeholder="Masukkan kode kupon" />
                    <x-button class="btn btn-primary" id="tombol_gunakan" href="terapkanKupon" indicator="Harap tunggu..." label="Gunakan" />
                </div>
            </div>
            @else
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                <i class="ki-filled ki-ticket-star fs-2"></i>
                Kupon "{{ $kode_kupon }}" telah diterapkan. Diskon: Rp {{ number_format($diskon) }}
                <x-button class="btn btn-danger" id="tombol_reset" href="resetKupon" indicator="Harap tunggu..." label="Hapus" />
            </div>
            @endif
            <div class="mb-8">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span class="fw-bold">Rp {{ number_format($this->subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Diskon:</span>
                        <span class="fw-bold text-success">- Rp {{ number_format($this->diskon) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="text-gray-700 me-2">Deposit</span>
                            <i class="ki-outline ki-information-5 text-gray-500 fs-6" 
                            data-bs-toggle="tooltip" 
                            title="Biaya deposit hanya untuk penyewa yang diluar domisili cabang pilihan"></i>
                        </div>
                        <span class="fw-bold text-{{ $deposit > 0 ? 'success' : 'danger' }}">
                            Rp {{ number_format($deposit) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Biaya Lainnya:</span>
                        <span>Rp {{ number_format($biaya_layanan + $biaya_materai) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top border-gray-300 pt-3 mt-2">
                        <h4 class="m-0">Total:</h4>
                        <h3 class="m-0 text-primary" data-grandtotal>Rp {{ number_format($this->grandtotal) }}</h3>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label for="jumlah_bayar" class="form-label">Jumlah Pembayaran:</label>
                <div class="position-relative w-md-400px" id="dialer_jumlah_bayar">
                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0" data-kt-dialer-control="decrease">
                        <i class="ki-filled ki-minus-squared fs-2"></i>
                    </button>
                    <x-form-input type="tel" name="jumlah_bayar" class="bg-transparent ps-12" placeholder="Masukkan jumlah pembayaran" readonly />
                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0" data-kt-dialer-control="increase">
                        <i class="ki-filled ki-plus-squared fs-2"></i>
                    </button>
                </div>
            </div>
            <div class="d-grid gap-3">
                <div class="d-flex gap-3">
                    <x-button class="btn btn-primary rounded-pill fw-bold py-3 flex-grow-1" id="tombol_sewa_desktop" href="sewa" icon="ki-filled ki-calendar fs-2 ms-2" indicator="Harap tunggu..." label="Sewa Sekarang" />
                    @if($this->selectedVariant->sale_price > 0)
                        <button class="btn btn-warning rounded-pill fw-bold py-3 flex-grow-1" 
                                wire:click="beli">
                            <i class="ki-filled ki-purchase fs-2 me-2"></i> Beli Sekarang
                        </button>
                        <button class="btn btn-success rounded-pill fw-bold py-3 flex-grow-1" 
                                wire:click="addToCart">
                            <i class="ki-filled ki-cart fs-2 me-2"></i> Keranjang
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-10">
        <div class="card-body p-8">
            <h4 class="mb-4">Jam Operasional Cabang</h4>
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam Buka</th>
                            <th>Jam Tutup</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->branchHours as $hour)
                        @php
                        // Format day of week
                        $formatDay = function ($day) {
                            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            return $days[$day] ?? $day;
                        };
                        @endphp
                        <tr>
                            <td>{{ $formatDay($hour->day_of_week) }}</td>
                            <td>{{ $hour->open_time }}</td>
                            <td>{{ $hour->close_time }}</td>
                            <td>
                                @if($hour->is_open)
                                    <span class="badge bg-success">Buka</span>
                                @else
                                    <span class="badge bg-danger">Tutup</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @section('custom_js')
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
    <script data-navigate-once>
        // Variabel global untuk menyimpan instance dialer
        // Fungsi untuk mengupdate dialer dengan nilai min dan max baru
        function updateJumlahBayarDialer(minAmount, maxAmount) {
            // Hancurkan dialer lama jika ada
            if (window.dialerObject) {
                window.dialerObject.destroy();
            }
            
            const dialerElement = document.querySelector("#dialer_jumlah_bayar");
            if (!dialerElement) return;
            
            const options = {
                min: minAmount,
                max: maxAmount,
                step: 5000,
                prefix: "",
                decimals: 0
            };
            
            window.dialerObject = new KTDialer(dialerElement, options);

            window.dialerObject.on('kt.dialer.change', function(){
                const currentValue = window.dialerObject.getValue();
                if(currentValue < maxAmount){
                    @this.set('jumlah_bayar', currentValue);
                } else {
                    @this.set('jumlah_bayar', maxAmount);
                }
            });
        }
        // Fungsi untuk mendapatkan nilai grandtotal dari elemen
        function getGrandTotalValue() {
            const grandtotalElement = document.querySelector('[data-grandtotal]');
            if (grandtotalElement) {
                return parseInt(grandtotalElement.textContent.replace(/\D/g, ''));
            }
            return 0;
        }
        function setupLivewireGrandtotalListener() {
            // Listen untuk event Livewire yang mengindikasikan update grandtotal
            window.addEventListener('grandtotal-updated', () => {
                setTimeout(() => {
                    const grandtotal = getGrandTotalValue();
                    const minAmount = grandtotal / 2;
                    const maxAmount = grandtotal;
                    console.log(grandtotal,minAmount);
                    updateJumlahBayarDialer(minAmount, maxAmount);
                }, 1000);
            });
        }

        // Fungsi untuk memastikan Snap.js sudah dimuat
        function ensureSnapIsLoaded(callback) {
            if (typeof window.snap !== 'undefined') {
                callback();
                return;
            }

            // Jika belum dimuat, tunggu hingga siap
            const checkSnap = setInterval(() => {
                if (typeof window.snap !== 'undefined') {
                    clearInterval(checkSnap);
                    callback();
                }
            }, 100);
        }

        function showSnap(payload) {
            ensureSnapIsLoaded(() => {
                // Validasi payload
                if (!payload) {
                    console.error('Payload is missing');
                    return;
                }
                
                const { token, rentCode } = payload;
                
                if (!token) {
                    console.error('Snap token is missing in payload', payload);
                    return;
                }
                
                // Jalankan pembayaran Snap
                window.snap.pay(token, {
                    onSuccess: function(result) {
                        Swal.fire({
                            title: 'Pembayaran Berhasil!',
                            text: 'Pelunasan transaksi telah berhasil.',
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = `/transaction/${rentCode}/view`;
                        });
                    },
                    onPending: function(result) {
                        Swal.fire({
                            title: 'Pembayaran Tertunda',
                            text: 'Silakan selesaikan pembayaran Anda.',
                            icon: 'info',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = `/transaction/${rentCode}/view`;
                        });
                    },
                    onError: function(error) {
                        Swal.fire({
                            title: 'Pembayaran Gagal',
                            text: 'Terjadi kesalahan saat memproses pembayaran.',
                            icon: 'error'
                        });
                    },
                    onClose: function() {
                        Swal.fire({
                            title: 'Pembayaran Dibatalkan',
                            text: 'Anda menutup jendela pembayaran.',
                            icon: 'warning'
                        });
                    }
                });
            });
        }

        // Event listener untuk event 'show-snap'
        function setupSnapListener() {
            window.addEventListener('show-snap', (event) => {
                showSnap(event.detail[0]);
            });
        }

        // Inisialisasi saat pertama kali load
        document.addEventListener('DOMContentLoaded', function() {
            setupSnapListener();
            initProductPage();
            setupLivewireGrandtotalListener();
    
            // Inisialisasi dialer pertama kali
            const grandtotal = getGrandTotalValue();
            const minAmount = grandtotal / 2;
            const maxAmount = grandtotal;
            
            updateJumlahBayarDialer(minAmount, maxAmount);
        });

        // Inisialisasi setelah navigasi Livewire
        document.addEventListener('livewire:navigated', function() {
            setupSnapListener();
            initProductPage();
            setupLivewireGrandtotalListener();
            // Inisialisasi dialer setelah navigasi
            setTimeout(() => {
                const grandtotal = getGrandTotalValue();
                const minAmount = grandtotal / 2;
                const maxAmount = grandtotal;
                
                updateJumlahBayarDialer(minAmount, maxAmount);
            }, 500);
            
            // Pastikan Snap.js tersedia setelah navigasi
            if (typeof window.snap === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
                script.setAttribute('data-client-key', '{{ env('MIDTRAMS_CLIENT_KEY') }}');
                document.head.appendChild(script);
            }
        });

        function initProductPage() {
            const hariInput = document.querySelector('[wire\\:model="jumlah_hari"]');
            if (hariInput) {
                const decreaseBtn = hariInput.previousElementSibling;
                const increaseBtn = hariInput.nextElementSibling;

                decreaseBtn.addEventListener('click', () => {
                    const current = parseInt(hariInput.value);
                    if (current > 1) {
                        hariInput.value = current - 1;
                        hariInput.dispatchEvent(new Event('input'));
                    }
                });

                increaseBtn.addEventListener('click', () => {
                    const current = parseInt(hariInput.value);
                    hariInput.value = current + 1;
                    hariInput.dispatchEvent(new Event('input'));
                });
            }

            const pickupDate = document.getElementById('pickupDate');
            if (pickupDate) {
                pickupDate.min = new Date().toISOString().split('T')[0];
            }

            const pickupTime = document.getElementById('pickupTime');
            if (pickupTime && !pickupTime.value) {
                const nextHour = new Date(new Date().getTime() + 60 * 60 * 1000);
                pickupTime.value = nextHour.toTimeString().substring(0, 5);
            }

            window.addEventListener('scroll', () => {
                const actionBar = document.getElementById('stickyActionBar');
                if (actionBar) {
                    actionBar.classList.toggle('show', window.scrollY > 100);
                }
            });
        }
    </script>
    @endsection
</div>