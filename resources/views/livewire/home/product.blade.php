<?php
use Carbon\Carbon;
use App\Models\Promo;
use Livewire\Volt\Component;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use Livewire\Attributes\Computed;

new class extends Component {
    public $category = '';
    public $brand = '';
    public $ratingFilter = '';
    public $priceRange = '';
    public $orderby = '';
    public $branch = '';
    public $search = '';
    public $selectedColor = '';
    public $selectedStorage = '';
    public $selectedProduct = '';
    #[Computed]
    public function branches()
    {
        return Branch::where('st', 'a')->orderBy('name')->pluck('name', 'id')->prepend('Pilih Cabang', '');
    }
    #[Computed]
    public function promos()
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);

        return Promo::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where(function ($query) use ($isWeekend) {
                $query->where('day_restriction', 'all')->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
            })
            ->first();
    }
    #[Computed]
    public function products()
    {
        return ProductBranch::with([
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
        ->limit(50)
        ->get();
    }
    public function set($type, $value)
    {
        $this->$type = $value;
        $this->dispatch('variant-selected');
    }
    public function placeholder()
    {
        return view('livewire.skeleton.category-home');
    }
};
?>
<div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4 mb-6">
        <div class="position-relative pb-2 mb-4 mb-md-0">
            <h2 class="fw-bold text-gray-900 mb-0 fs-2">Untuk Kamu</h2>
            <div class="position-absolute bottom-0 start-0 w-50 h-2px bg-primary rounded"></div>
        </div>
    </div>
    @if ($this->products->count() > 0)
        <div class="row row-cols-2 row-cols-sm-2 row-cols-xl-6 g-3 mb-2">
            @foreach ($this->products as $productId => $variations)
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
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-between p-2">
                        <div class="mb-3">
                            <div class="card bg-light mb-3 d-flex align-items-center justify-content-center position-relative"
                                style="height: 180px;">
                                <img alt="" class="img-fluid" style="height: 180px;" data-bs-toggle="drawer"
                                    src="{{ $colorImage ?? $product->image }}" loading="lazy" wire:loading.class.delay="opacity-50">
                                <div wire:loading.delay class="position-absolute top-50 start-50 translate-middle">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <a class="text-decoration-none hover:text-primary text-sm fw-medium text-mono d-block px-2"
                                data-bs-toggle="drawer" href="#drawers_shop_product_details">
                                {{ $product->name }}
                            </a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                            <div class="d-flex align-items-center gap-1">
                                <span class="text-sm fw-medium text-mono">
                                    Rp{{ number_format($variations->rent_price, 0, ',', '.') }} /hari
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                {{-- <button class="btn btn-outline-dark btn-sm ms-1" data-bs-toggle="drawer"
                                    href="#drawers_shop_cart">
                                    <i class="ki-filled ki-handcart"></i> Add
                                </button> --}}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                            <div class="d-flex align-items-center gap-1">
                                <div class="badge bg-warning rounded-pill">
                                    <i class="ki-solid ki-star text-white me-1"></i>
                                    {{ number_format($product->averageRating()) }}
                                </div>
                                @if ($product->ratings->count() > 0)
                                <span class="text-sm fw-medium text-mono">
                                    ({{ $product->ratings->count() }})
                                </span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                {{ $variations->branch->name }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex align-items-center gap-1">
                                {{-- <button class="btn btn-outline-dark btn-sm ms-1" data-bs-toggle="drawer"
                                    href="#drawers_shop_cart">
                                    <i class="ki-filled ki-handcart"></i> Tambah
                                </button> --}}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ $routeProduct }}" wire:navigate class="btn btn-outline-dark btn-sm ms-1">
                                    <i class="ki-filled ki-handcart"></i> Sewa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-15">
            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here.png') }}"
                class="w-200px mb-5 theme-light-show" alt="No Products" loading="lazy">
            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here-dark.png') }}"
                class="w-200px mb-5 theme-dark-show" alt="No Products" loading="lazy">
            <h3 class="text-gray-600">Produk tidak ditemukan</h3>
            <p class="text-muted">Silakan cari dengan kata kunci lain atau pilih cabang berbeda</p>
            <button class="btn btn-primary rounded-pill px-6" wire:click="$set('search', '')">
                Reset Pencarian
            </button>
        </div>
    @endif
</div>
