<?php

use Carbon\Carbon;
use App\Models\Promo;
use App\Models\Master\Brand;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use App\Models\Master\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{computed, state};

state([
    'orderby' => '',
    'branch' => '',
    'brand' => '',
    'category' => '',
    'priceRange' => '',
    'ratingFilter' => '',
    'search' => '',
    'selectedColor' => null,
    'selectedStorage' => null,
    'selectedProduct' => null,
    'showViewAll' => true,
]);

$productBranch = computed(function() {
    
    $list = ProductBranch::with([
        'product.category', 
        'branch'
    ])
    ->whereIn('id', function($query) {
    $query->select(DB::raw('MIN(id)'))
          ->from('product_branches')
          ->where('is_publish', 1)
          ->groupBy('product_id', 'branch_id');
    })
    ->when($this->branch, function($query) {
        $query->whereHas('product', function($q) {
            $q->where('branch_id', $this->branch);
        });
    })
    ->when($this->category, function($query) {
        $query->whereHas('product', function($q) {
            $q->where('category_id', $this->category);
        });
    })
    ->when($this->brand, function($query) {
        $query->whereHas('product', function($q) {
            $q->where('brand_id', $this->brand);
        });
    })
    ->when($this->search, function($query) {
        $query->whereHas('product', function($q) {
            $q->where('name', 'like', '%'.$this->search.'%');
        });
    })
    ->when($this->ratingFilter, function($query) {
        $query->whereHas('product', function($q) {
            $q->whereHas('ratings', function($r) {
                $r->select(DB::raw('AVG(rating) as avg_rating'))
                  ->groupBy('product_id')
                  ->havingRaw('AVG(rating) >= ?', [$this->ratingFilter]);
            });
        });
    })
    ->when($this->priceRange, function($query) {
        $range = explode('-', $this->priceRange);
        if (count($range) == 2) {
            $min = $range[0] !== '' ? (int)$range[0] : null;
            $max = $range[1] !== '' ? (int)$range[1] : null;

            if (!is_null($min)) {
                $query->where('rent_price', '>=', $min);
            }
            if (!is_null($max)) {
                $query->where('rent_price', '<=', $max);
            }
        } elseif (count($range) == 1 && $this->priceRange !== '') {
            // Handle cases like "5000000-" (greater than 5 million)
            $min = (int)$range[0];
            $query->where('rent_price', '>=', $min);
        }
    })
    ->when($this->orderby, function($query) {
        if($this->orderby == 'harga-tertinggi') {
            $query->orderBy('rent_price', 'desc');
        } elseif($this->orderby == 'harga-terendah') {
            $query->orderBy('rent_price', 'asc');
        } elseif($this->orderby == 'rating-tertinggi') {
            $query->whereHas('product', function($q) {
                $q->withAvg('ratings', 'rating')
                  ->orderBy('ratings_avg_rating', 'desc');
            });
        } elseif($this->orderby == 'terbaru') {
            $query->whereHas('product', function($q) {
                $q->orderBy('created_at', 'desc');
            });
        } else {
            // Default sorting by relevance or other criteria
            $query->whereHas('product', function($q) {
                $q->orderBy('name'); // Example: alphabetical order
            });
        }
    })
    ->get();
    return $list;
});

$promo = computed(function() {
    $now = now();
    $dayOfWeek = $now->dayOfWeek;
    $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);

    return Promo::where('is_active', true)
        ->where('start_date', '<=', $now)
        ->where('end_date', '>=', $now)
        ->where(function($query) use ($isWeekend) {
            $query->where('day_restriction', 'all')
                ->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
        })
        ->first();
});

$set = function($type, $value) {
    $this->$type = $value;
    $this->dispatch('variant-selected');
};

// Add missing addToCart method
$addToCart = function($type, $productBranchId = null) {
    // Implement your add to cart logic here
    // For now, we'll just show an alert
    if ($productBranchId) {
        $this->js("alert('Adding product to cart: " . $productBranchId . "')");
    } else {
        $this->js("alert('Adding product to cart')");
    }
};
?>
<div class="row">
    <style>
        /* Filter Section */
        .filter-card {
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: none;
            position: sticky;
            top: 20px;
        }
        
        .filter-card .card-header {
            background: transparent;
            border-bottom: 1px solid #EAECF0;
            padding: 1rem 1.25rem;
        }
        
        .filter-card .card-title h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #1D2939;
        }
        
        .filter-card .card-body {
            padding: 1.25rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #D0D5DD;
            transition: var(--smooth-transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 85, 250, 0.1);
        }
        
        .filter-section {
            margin-bottom: 1.5rem;
        }
        
        .filter-section:last-child {
            margin-bottom: 0;
        }
        
        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #344054;
            font-size: 0.875rem;
        }
        
        .filter-reset {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 1rem;
            transition: var(--smooth-transition);
        }
        
        .filter-reset:hover {
            color: #2c40d8;
        }
        
        /* Product Card */
        .product-card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--smooth-transition);
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        
        .product-image-container {
            height: 200px;
            background: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        
        .product-image {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: var(--smooth-transition);
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .stock-badge, .promo-badge {
            position: absolute;
            top: 12px;
            z-index: 2;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
        }
        
        .stock-badge {
            left: 12px;
            background: rgba(25, 135, 84, 0.9);
            color: white;
        }
        
        .promo-badge {
            right: 12px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .product-title {
            font-weight: 600;
            font-size: 1rem;
            line-height: 1.4;
            color: #1D2939;
            margin-bottom: 0.75rem;
            height: 44px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .price-container {
            margin-bottom: 1rem;
        }
        
        .rental-price, .purchase-option {
            margin-bottom: 1rem;
        }
        
        .current-price {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color);
        }
        
        .price-unit {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .original-price {
            font-size: 0.875rem;
            color: var(--secondary-color);
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        
        .purchase-option {
            background-color: rgba(25, 135, 84, 0.05);
            border-radius: 8px;
            padding: 0.75rem;
            border-left: 3px solid var(--success-color);
        }
        
        .purchase-label {
            font-size: 0.75rem;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
        }
        
        .purchase-price {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--success-color);
        }
        
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--secondary-color);
            font-size: 0.75rem;
            margin: 1rem 0;
        }
        
        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #EAECF0;
        }
        
        .separator:not(:empty)::before {
            margin-right: 0.5rem;
        }
        
        .separator:not(:empty)::after {
            margin-left: 0.5rem;
        }
        
        .rating-location {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .rating, .location {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .rating i {
            color: #FFC107;
        }
        
        .location i {
            color: var(--danger-color);
        }
        
        /* Variant Options */
        .variant-options {
            margin-bottom: 1.25rem;
        }
        
        .variant-label {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #344054;
        }
        
        .color-options, .storage-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .color-option {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--smooth-transition);
            position: relative;
        }
        
        .color-option.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary-color);
        }
        
        .color-option.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .color-option-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1D2939;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--smooth-transition);
            margin-bottom: 0.5rem;
        }
        
        .color-option:hover .color-option-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .storage-option {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #F8F9FA;
            border: 1px solid #EAECF0;
            cursor: pointer;
            transition: var(--smooth-transition);
        }
        
        .storage-option.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .storage-option.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: var(--smooth-transition);
            border: 1px solid #EAECF0;
        }
        
        .btn-rent {
            background: rgba(67, 85, 250, 0.1);
            color: var(--primary-color);
        }
        
        .btn-rent:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-buy {
            background: rgba(255, 193, 7, 0.1);
            color: #E4A00B;
        }
        
        .btn-buy:hover {
            background: #E4A00B;
            color: white;
        }
        
        .btn-cart {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-color);
        }
        
        .btn-cart:hover {
            background: var(--success-color);
            color: white;
        }
        
        /* No Products State */
        .no-products {
            padding: 3rem 1rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .no-products img {
            max-width: 200px;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }
        
        .no-products h3 {
            color: #344054;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .no-products p {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }
        
        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
        }
        
        .spinner-border {
            width: 2rem;
            height: 2rem;
            color: var(--primary-color);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .filter-column {
                width: 100% !important;
                margin-bottom: 1.5rem;
            }
            
            .products-column {
                width: 100% !important;
            }
        }
        
        @media (max-width: 576px) {
            .product-card {
                max-width: 320px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animation for filter changes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .product-item {
            animation: fadeIn 0.5s ease;
        }
        
        /* Custom range slider for price filter */
        .price-slider {
            padding: 0 0.5rem;
        }
        
        .price-slider .form-range {
            height: 6px;
        }
        
        .price-slider .form-range::-webkit-slider-thumb {
            background: var(--primary-color);
        }
        
        .price-slider .form-range::-moz-range-thumb {
            background: var(--primary-color);
        }
        
        .price-values {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
    </style>
    <div class="col-lg-3 filter-column">
        <div class="filter-card card bg-light mb-4">
            <div class="card-header">
                <div class="card-title">
                    <h2><i class="bi bi-funnel me-2"></i>Filter</h2>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Input -->
                <div class="filter-section">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search" 
                            class="form-control" 
                            placeholder="Cari produk..."
                            aria-label="Cari produk">
                    </div>
                </div>
                
                <!-- Brand Filter - Added missing Brand filter -->
                <div class="filter-section">
                    <x-form-group name="branch" label="Cabang">
                        <x-form-select
                            name="branch"
                            class="form-select form-select-solid fw-bold"
                            :options="Branch::pluck('name', 'id')
                                ->prepend('Pilih Cabang', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                <div class="filter-section">
                    <x-form-group name="merek" label="Merek">
                        <x-form-select
                            name="merek"
                            class="form-select form-select-solid fw-bold"
                            :options="Brand::where('st', 'a')->pluck('name', 'id')
                                ->prepend('Pilih Merek', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <!-- Category Filter -->
                <div class="filter-section">
                    <x-form-group name="category" label="Kategori">
                        <x-form-select
                            name="category"
                            class="form-select form-select-solid fw-bold"
                            :options="Category::where('st', 'a')->pluck('name', 'id')
                                ->prepend('Pilih Kategori', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <!-- Rating Filter -->
                <div class="filter-section">
                    <label class="filter-label">Rating</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="ratingFilter" id="ratingAll" value="" checked>
                            <label class="form-check-label" for="ratingAll">Semua Rating</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="ratingFilter" id="rating5" value="5">
                            <label class="form-check-label" for="rating5">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                (5)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="ratingFilter" id="rating4" value="4">
                            <label class="form-check-label" for="rating4">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star text-warning"></i>
                                (4+)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="ratingFilter" id="rating3" value="3">
                            <label class="form-check-label" for="rating3">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star text-warning"></i>
                                <i class="bi bi-star text-warning"></i>
                                (3+)
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Price Filter -->
                <div class="filter-section">
                    <label class="filter-label">Rentang Harga</label>
                    <div class="mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="priceAll" value="" checked>
                            <label class="form-check-label" for="priceAll">Semua Harga</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="price1" value="0-100000">
                            <label class="form-check-label" for="price1">Di bawah Rp 100.000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="price2" value="100000-500000">
                            <label class="form-check-label" for="price2">Rp 100.000 - Rp 500.000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="price3" value="500000-1000000">
                            <label class="form-check-label" for="price3">Rp 500.000 - Rp 1.000.000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="price4" value="1000000-5000000">
                            <label class="form-check-label" for="price4">Rp 1.000.000 - Rp 5.000.000</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.change="priceRange" id="price5" value="5000000-">
                            <label class="form-check-label" for="price5">Di atas Rp 5.000.000</label>
                        </div>
                    </div>
                </div>
                
                <!-- Reset Filters -->
                <a href="#" class="filter-reset" wire:click="$set('search', '')">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filter
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-9 products-column">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">Daftar Produk</h3>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Urutkan:</span>
                <x-form-select
                    name="orderby"
                    class="form-select form-select-solid fw-bold"
                    :options="[
                        'paling-sesuai' => 'Paling Sesuai',
                        'harga-tertinggi' => 'Harga Tertinggi',
                        'harga-terendah' => 'Harga Terendah',
                        'rating-tertinggi' => 'Rating Tertinggi',
                        'terbaru' => 'Terbaru'
                    ]"
                    modifier="change"
                />
            </div>
        </div>
        @if($this->productBranch->count() > 0)
        <div class="row g-4" id="products">
            @foreach($this->productBranch as $variations)
            @php
                $product = $variations->product;
                $totalStock = $variations->count();
                
                // Get available variants based on selections
                $availableVariants = $variations;
                if ($this->selectedColor) {
                    $availableVariants = $availableVariants->where('color_id', $this->selectedColor);
                }
                if ($this->selectedStorage) {
                    $availableVariants = $availableVariants->where('storage_id', $this->selectedStorage);
                }
                
                // Determine product image based on selected color
                $colorImage = null;
                if ($variations->color) {
                    $colorImage = asset('storage/product/' . $product->slug . '-' . Str::slug($variations->color->value) . '.png');
                }
                
                // Determine route based on auth status
                if(Auth::check()) {
                    if(Auth::user()->getRoleNames()[0] == "Onboarding") {
                        $routeProduct = route('onboarding');
                    } else {
                        if(Auth::user()->st == "verified" && Auth::user()->isNotBanned() && !Auth::user()->deleted_at) {
                            // Fixed undefined variable $cabang - using branch from variant instead
                            $routeProduct = route('product.show', [
                                'product' => $product,
                                'branch' => $variations->branch->slug ?? 'default'
                            ]);
                        } else {
                            $routeProduct = route('home');
                        }
                    }
                } else {
                    $routeProduct = route('login');
                }
            @endphp
            
            <div class="col-12 col-sm-6 col-md-4 col-xl-3 product-item">
                <div class="product-card card h-100">
                    <!-- Product Image -->
                    <div class="product-image-container">
                        <img src="{{ $colorImage ?? $product->image }}" 
                            class="product-image" 
                            alt="{{ $product->name }}"
                            loading="lazy"
                            wire:loading.class.delay="opacity-50">
                            
                        <!-- Loading indicator -->
                        <div wire:loading.delay class="loading-overlay">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body" wire:ignore.self>
                        <!-- Product Name -->
                        <h3 class="product-title">
                            {{ $product->name }}
                        </h3>
                        
                        <!-- Price Display -->
                        <div class="price-container">
                            @if($variations->sale_price > 0)
                                <!-- Rental Price Section -->
                                <div class="rental-price">
                                    <div class="d-flex align-items-baseline flex-wrap">
                                        <span class="current-price">
                                            Rp{{ number_format($variations->rent_price, 0, ',', '.') }}
                                        </span>
                                        <span class="price-unit">/hari</span>
                                    </div>
                                </div>
                                
                                <!-- Divider -->
                                <div class="separator">atau</div>
                                
                                <!-- Purchase Option Section -->
                                <div class="purchase-option">
                                    <div class="purchase-label">Beli Sekarang</div>
                                    <div class="d-flex align-items-baseline flex-wrap">
                                        <span class="purchase-price">
                                            Rp{{ number_format($variations->sale_price, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <!-- Rental Only Section -->
                                <div class="rental-price">
                                    <div class="d-flex align-items-baseline flex-wrap">
                                        <span class="current-price">
                                            Rp{{ number_format($variations->rent_price, 0, ',', '.') }}
                                        </span>
                                        <span class="price-unit">/hari</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Rating & Location -->
                        <div class="rating-location">
                            <span class="rating">
                                <i class="bi bi-star-fill"></i>
                                {{ number_format($product->averageRating()) }}
                                @if($product->ratings->count() > 0)
                                    <span>({{ $product->ratings->count() }})</span>
                                @endif
                            </span>
                            @if(!$this->branch)
                            <span class="location">
                                <i class="bi bi-geo-alt"></i> 
                                {{ $variations->branch->city->name ?? 'Unknown' }}
                            </span>
                            @endif
                        </div>
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="{{ $routeProduct }}" wire:navigate
                                class="btn-action btn-rent" data-bs-toggle="tooltip" data-bs-placement="top" title="Sewa Sekarang">
                                <i class="bi bi-calendar-check"></i>
                            </a>
                            @if($variations->sale_price > 0)
                                <button class="btn-action btn-buy" wire:click="addToCart('buy', {{ $variations->id }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Beli Sekarang">
                                    <i class="bi bi-bag"></i>
                                </button>
                                <button class="btn-action btn-cart" wire:click="addToCart({{ $variations->id }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah ke Keranjang">
                                    <i class="bi bi-cart"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="no-products">
            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here.png') }}" 
                    class="theme-light-show" 
                    alt="No Products"
                    loading="lazy">
            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here-dark.png') }}" 
                    class="theme-dark-show" 
                    alt="No Products"
                    loading="lazy">
            <h3>Produk tidak ditemukan</h3>
            <p>Silakan cari dengan kata kunci lain atau pilih cabang berbeda</p>
            <button class="btn btn-primary rounded-pill px-4 py-2" wire:click="$set('search', '')">
                <i class="bi bi-arrow-repeat me-2"></i>Reset Pencarian
            </button>
        </div>
        @endif
    </div>
</div>
    @section('custom_js')
    <script data-navigate-once>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Price range slider functionality
        const priceRange = document.getElementById('priceRange');
        if (priceRange) {
            priceRange.addEventListener('input', function() {
                const minPrice = 0;
                const maxPrice = 10000000;
                const currentValue = this.value;
                
                // Update display values (you could connect this to your Livewire component)
                document.querySelector('.price-values span:first-child').textContent = 'Rp ' + formatNumber(currentValue);
            });
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
    </script>
    @endsection
</div>