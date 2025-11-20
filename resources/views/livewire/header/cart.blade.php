<?php
use App\Models\Promo;
use App\Models\Transaction\Cart;
use Illuminate\Support\Facades\Cache;
use function Livewire\Volt\{state, computed};

state([
    'cartItems' => fn() => Auth::check() ? Auth::user()->carts()->with(['productable.product.category', 'productable.branch'])->get() : collect(),
    'subtotal' => 0,
    'totalDiscount' => 0,
    'total' => 0,
]);

$refreshCart = function() {
    if (auth()->check()) {
        $this->cartItems = auth()->user()->carts()->with(['productable.product.category', 'productable.branch'])->get();
        $this->subtotal = $this->cartItems->sum(function($item) {
            return $item->type == 'rent' ? $item->price * $item->days : $item->price * $item->quantity;
        });
        $this->totalDiscount = $this->calculateDiscounts();
        $this->total = $this->subtotal - $this->totalDiscount;
    }
};

$calculateDiscounts = function() {
    $totalDiscount = 0;
    
    foreach ($this->cartItems as $item) {
        $product = $item->productable->product;
        $branch = $item->productable->branch;
        $category = $product->category;

        // Fetch applicable promotions
        $promos = Promo::where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function ($query) use ($product, $branch, $category) {
                $query->whereHas('products', fn($q) => $q->where('product_id', $product->id))
                      ->orWhereHas('branches', fn($q) => $q->where('branch_id', $branch->id))
                      ->orWhereHas('categories', fn($q) => $q->where('category_id', $category->id));
            })
            ->get();

        foreach ($promos as $promo) {
            $itemTotal = $item->type == 'rent' ? $item->price * $item->days : $item->price * $item->quantity;
            $discount = $promo->discount_type == 'percentage'
                ? $itemTotal * ($promo->discount_value / 100)
                : min($promo->discount_value, $itemTotal); // Cap fixed discount at item total

            // Apply max_discount if defined in pivot tables
            $maxDiscount = $promo->products()->where('product_id', $product->id)->first()?->pivot->max_discount
                ?? $promo->branches()->where('branch_id', $branch->id)->first()?->pivot->max_discount
                ?? $promo->categories()->where('category_id', $category->id)->first()?->pivot->max_discount
                ?? PHP_INT_MAX;

            $totalDiscount += min($discount, $maxDiscount);
        }
    }

    return Cache::remember('cart_discounts.' . auth()->id(), now()->addMinutes(10), fn() => $totalDiscount);
};

$incrementQuantity = function($cartId) {
    $cart = Cart::find($cartId);
    if ($cart) {
        if ($cart->type == 'rent') {
            $cart->increment('days');
            $cart->end_date = \Carbon\Carbon::parse($cart->start_date)->addDays($cart->days);
            $cart->update();
        } else {
            $cart->increment('quantity');
        }
        $this->refreshCart();
        event(new \App\Events\CartUpdated(auth()->id()));
        $this->dispatch('toast-success', type: 'success', message: 'Kuantitas diperbarui');
    }
};

$decrementQuantity = function($cartId) {
    $cart = Cart::find($cartId);
    if ($cart) {
        if ($cart->type == 'rent') {
            if ($cart->days > 1) {
                $cart->decrement('days');
                $cart->end_date = \Carbon\Carbon::parse($cart->start_date)->addDays($cart->days);
                $cart->update();
            } else {
                $this->removeItem($cartId);
            }
        } else {
            if ($cart->quantity > 1) {
                $cart->decrement('quantity');
            } else {
                $this->removeItem($cartId);
            }
        }
        $this->refreshCart();
        event(new \App\Events\CartUpdated(auth()->id()));
        $this->dispatch('toast-success', type: 'success', message: 'Kuantitas diperbarui');
    }
};

$removeItem = function($cartId) {
    $cart = Cart::find($cartId);
    if ($cart) {
        $cart->delete();
        $this->refreshCart();
        event(new \App\Events\CartUpdated(auth()->id()));
        $this->dispatch('toast-success', type: 'success', message: 'Item dihapus dari keranjang');
    }
};
?>
<div 
    id="kt_shopping_cart" 
    class="bg-body" 
    data-kt-drawer="true" 
    data-kt-drawer-name="cart" 
    data-kt-drawer-activate="true" 
    data-kt-drawer-overlay="true" 
    data-kt-drawer-width="{default:'300px', 'md': '500px'}" 
    data-kt-drawer-direction="end" 
    data-kt-drawer-toggle="#kt_drawer_shopping_cart_toggle" 
    data-kt-drawer-close="#kt_drawer_shopping_cart_close" 
    wire:ignore.self 
    {{-- if(Auth::check() && Auth::user()->getRoleNames()[0] == "Konsumen") --}}
        {{-- wire:poll.20s="loadCarts" --}}
    {{-- endif --}}
>
    <div class="card card-flush w-100 rounded-0">
        <!-- Header -->
        <div class="card-header">
            <h3 class="card-title text-gray-900 fw-bold">Keranjang Belanja</h3>
            <div class="card-toolbar">
                <div class="btn btn-sm btn-icon btn-active-light-primary" id="kt_drawer_shopping_cart_close">
                    <i class="ki-outline ki-cross fs-2"></i>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="card-body hover-scroll-overlay-y h-400px pt-5">
            @auth
                @if($cartItems->count() > 0)
                    @foreach($cartItems as $item)
                        <div class="d-flex flex-stack mb-6">
                            <div class="d-flex flex-column me-3" style="width: 60%">
                                <div class="mb-3">
                                    <a href="{{ $item->type == 'rent' ? route('product-rent.show', ['productRent' => $item->productable_id]) : route('product-sale.show', ['productSale' => $item->productable_id]) }}" 
                                    class="text-gray-800 text-hover-primary fs-4 fw-bold">
                                        {{ $item->productable->product->name }}
                                    </a>
                                    <span class="text-gray-500 fw-semibold d-block">
                                        {{ $item->productable->product->category->name }}
                                    </span>
                                    <span class="badge bg-{{ $item->type == 'rent' ? 'primary' : 'success' }} mt-2">
                                        {{ $item->type == 'rent' ? 'Sewa' : 'Beli' }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-gray-800 fs-5">Rp {{ number_format($item->price) }}</span>
                                    <span class="text-muted mx-2">x</span>
                                    <span class="fw-bold text-gray-800 fs-5 me-3">
                                        {{ $item->type == "sale" ? $item->quantity : $item->days }}
                                    </span>
                                    <button wire:click="decrementQuantity({{ $item->id }})" class="btn btn-sm btn-light-success btn-icon w-25px h-25px me-2" wire:loading.attr="disabled">
                                        <i class="ki-outline ki-minus fs-4"></i>
                                    </button>
                                    <button wire:click="incrementQuantity({{ $item->id }})" class="btn btn-sm btn-light-success btn-icon w-25px h-25px me-5" wire:loading.attr="disabled">
                                        <i class="ki-outline ki-plus fs-4"></i>
                                    </button>
                                    {{ $item->type == "rent" ? 'hari' : '' }}
                                </div>
                                @if($item->type == 'rent')
                                    <div class="mt-3">
                                        <div class="text-gray-600 fs-7">
                                            <i class="ki-outline ki-calendar fs-4 me-2"></i>
                                            {{ \Carbon\Carbon::parse($item->start_date)->translatedFormat('d M Y') }} - 
                                            {{ \Carbon\Carbon::parse($item->start_date)->addDays($item->days)->translatedFormat('d M Y') }}
                                        </div>
                                    </div>
                                @endif
                                <!-- Display per-item discount -->
                                @php
                                    $itemDiscount = 0;
                                    $promos = \App\Models\Promo::where('active', true)
                                        ->where('start_date', '<=', now())
                                        ->where('end_date', '>=', now())
                                        ->where(function ($query) use ($item) {
                                            $query->whereHas('products', fn($q) => $q->where('product_id', $item->productable->product->id))
                                                ->orWhereHas('branches', fn($q) => $q->where('branch_id', $item->productable->branch->id))
                                                ->orWhereHas('categories', fn($q) => $q->where('category_id', $item->productable->product->category->id));
                                        })->get();
                                    foreach ($promos as $promo) {
                                        $itemTotal = $item->type == 'rent' ? $item->price * $item->days : $item->price * $item->quantity;
                                        $discount = $promo->discount_type == 'percentage'
                                            ? $itemTotal * ($promo->discount_value / 100)
                                            : min($promo->discount_value, $itemTotal);
                                        $maxDiscount = $promo->products()->where('product_id', $item->productable->product->id)->first()?->pivot->max_discount
                                            ?? $promo->branches()->where('branch_id', $item->productable->branch->id)->first()?->pivot->max_discount
                                            ?? $promo->categories()->where('category_id', $item->productable->product->category->id)->first()?->pivot->max_discount
                                            ?? PHP_INT_MAX;
                                        $itemDiscount += min($discount, $maxDiscount);
                                    }
                                @endphp
                                @if($itemDiscount > 0)
                                    <div class="text-success fs-7 mt-2">
                                        Diskon: -Rp {{ number_format($itemDiscount) }}
                                    </div>
                                @endif
                            </div>
                            <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                                <img src="{{ $item->productable->product->image ?? asset('assets/media/stock/600x400/img-1.jpg') }}" 
                                    alt="{{ $item->productable->product->name }}" class="object-fit-cover" />
                            </div>
                            <button wire:click="removeItem({{ $item->id }})" class="btn btn-sm btn-icon btn-light-danger position-absolute top-0 end-0 mt-2 me-2" wire:loading.attr="disabled">
                                <i class="ki-outline ki-trash fs-2"></i>
                            </button>
                        </div>
                        <div class="separator separator-dashed my-3"></div>
                    @endforeach
                    <!-- Footer -->
                    <div class="card-footer">
                        <div class="d-flex flex-stack mb-5">
                            <span class="fw-bold text-gray-600">Subtotal</span>
                            <span class="text-gray-800 fw-bolder fs-5">Rp {{ number_format($subtotal) }}</span>
                        </div>
                        @if($totalDiscount > 0)
                            <div class="d-flex flex-stack mb-5">
                                <span class="fw-bold text-gray-600">Diskon</span>
                                <span class="text-danger fw-bolder fs-5">- Rp {{ number_format($totalDiscount) }}</span>
                            </div>
                        @endif
                        <div class="d-flex flex-stack mb-7">
                            <span class="fw-bold text-gray-600">Total</span>
                            <span class="text-primary fw-bolder fs-4">Rp {{ number_format($total) }}</span>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('checkout') }}" wire:navigate class="btn btn-primary w-100" wire:loading.attr="disabled">
                                <i class="ki-outline ki-basket fs-2 me-2"></i> Proses Checkout
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-center py-10">
                        <img src="{{ asset('assets/media/illustrations/shoppings.png') }}" class="w-150px theme-light-show mb-5" alt="Empty Cart">
                        <img src="{{ asset('assets/media/illustrations/shoppings-dark.png') }}" class="w-150px theme-dark-show mb-5" alt="Empty Cart">
                        <h4 class="text-gray-600 mb-3">Keranjang belanja kosong</h4>
                        <p class="text-muted">Tambahkan produk terlebih dahulu</p>
                        <a href="{{ route('home') }}" class="btn btn-primary">Jelajahi Produk</a>
                    </div>
                @endif
            @endauth
        </div>
        
        @auth
            @php
                // Initialize totals outside the loop
                $subtotal = 0;
                $diskon = 10000; // Fixed discount for all items
                $total = 0;
                
                // Get all cart items once
                $cartItems = $cartItems;
                
                // Calculate totals
                foreach ($cartItems as $item) {
                    if($item->type == "rent") {
                        $subtotal += $item->price * $item->days; // Use += to accumulate
                    } else {
                        $subtotal += $item->price * $item->quantity; // Use += to accumulate
                    }
                }
                $total = $subtotal - $diskon;
            @endphp

            @if($cartItems->count() > 0)
                <!-- Footer -->
                <div class="card-footer">
                    <div class="d-flex flex-stack mb-5">
                        <span class="fw-bold text-gray-600">Subtotal</span>
                        <span class="text-gray-800 fw-bolder fs-5">Rp {{ number_format($subtotal) }}</span>
                    </div>
                    
                    @if($diskon > 0)
                    <div class="d-flex flex-stack mb-5">
                        <span class="fw-bold text-gray-600">Diskon</span>
                        <span class="text-danger fw-bolder fs-5">- Rp {{ number_format($diskon) }}</span>
                    </div>
                    @endif
                    
                    <div class="d-flex flex-stack mb-7">
                        <span class="fw-bold text-gray-600">Total</span>
                        <span class="text-primary fw-bolder fs-4">Rp {{ number_format($total) }}</span>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('checkout') }}" wire:navigate class="btn btn-primary w-100">
                            <i class="ki-outline ki-basket fs-2 me-2"></i> Proses Checkout
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    </div>
</div>