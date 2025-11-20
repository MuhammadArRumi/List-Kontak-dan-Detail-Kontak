<?php
use Carbon\Carbon;
use App\Models\Promo;
use Livewire\Volt\Component;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use Livewire\Attributes\Computed;

new class extends Component {
    public $product;
    public $branch;
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
        return ProductBranch::with(['product.category', 'color', 'storage', 'branch'])
            ->where('product_id', '!=', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->where('is_publish', 1)
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('description_rent', 'like', '%' . $this->search . '%');
                });
            })
            ->get()
            ->groupBy('product_id');
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
    @if ($this->products->count() > 0)
    <div class="row row-cols-2 row-cols-sm-2 row-cols-xl-6 g-3 mb-2">
        @foreach ($this->products as $productId => $variations)
            @php
            $firstVariant = $variations->first();
            $product = $firstVariant->product;
            $totalStock = $variations->sum('stock');
            // Get available variants based on selections
            $availableVariants = $variations;
            if ($this->selectedColor) {
                $availableVariants = $availableVariants->where('color_id', $this->selectedColor);
            }
            if ($this->selectedStorage) {
                $availableVariants = $availableVariants->where('storage_id', $this->selectedStorage);
            }
            $selectedVariant = $availableVariants->first() ?? $firstVariant;
            // Get unique colors and storages
            $uniqueColors = $variations->unique('color_id')->filter(fn($v) => $v->color);
            $uniqueStorages = $variations->unique('storage_id')->filter(fn($v) => $v->storage);
            // Determine product image based on selected color
            // $colorImage = null;
            if ($selectedVariant->color) {
                $colorImage = asset(
                    'storage/product/' .
                        $product->slug .
                        '-' .
                        Str::slug($selectedVariant->color->value) .
                        '.png',
                );
            }
            // Determine route based on auth status
            $routeProduct = route('product.show', [
                'product' => $product->slug,
                'branch' => $this->branch->slug,
            ]);
            // Promo calculations
            $promo = $this->promos;
            $showPromo =
                $promo &&
                ($promo->scope === 'all' ||
                    ($promo->scope === 'products' && $promo->products->contains($product->id))) &&
                ($selectedVariant->sale_price >= ($promo->min_order_amount ?? 0) ||
                    $selectedVariant->rent_price >= ($promo->min_order_amount ?? 0)) &&
                ($promo->max_uses === null || $promo->max_uses > $promo->usages->count()) &&
                in_array($promo->type, ['percentage', 'fixed_amount']);

            // Calculate discounted prices
            $discountedSale = $selectedVariant->sale_price;
            $discountedRent = $selectedVariant->rent_price;
            if ($showPromo) {
                if ($promo->type === 'percentage') {
                    $discountedSale = $selectedVariant->sale_price * (1 - $promo->value / 100);
                    $discountedRent = $selectedVariant->rent_price * (1 - $promo->value / 100);
                } elseif ($promo->type === 'fixed_amount') {
                    $discountedSale = max(0, $selectedVariant->sale_price - $promo->value);
                    $discountedRent = max(0, $selectedVariant->rent_price - $promo->value);
                }
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
                    <div class="text-sm fw-medium text-mono px-2 mb-3">
                        <label class="form-label fw-semibold d-block mb-2">Warna</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($uniqueColors as $variant)
                                @if ($variant->color)
                                    @php
                                        $isActive = $this->selectedColor == $variant->color_id;
                                        $isDisabled =
                                            $this->selectedStorage &&
                                            !$variations->contains(
                                                fn($v) => $v->color_id == $variant->color_id &&
                                                    $v->storage_id == $this->selectedStorage,
                                            );
                                    @endphp
                                    <div data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="{{ $variant->color->value }}"
                                        class="color-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                        style="background-color: {{ $variant->warna($variant->color->value) }};"
                                        wire:click="set('selectedColor', {{ $variant->color_id }})"
                                        aria-label="Pilih warna {{ $variant->color->value }}"
                                        @if ($isDisabled) aria-disabled="true" @endif>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <!-- Storage Options -->
                    <div class="text-sm fw-medium text-mono px-2 mb-3">
                        <label class="form-label fw-semibold d-block mb-2">Storage</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($uniqueStorages as $variant)
                                @php
                                    $isActive = $this->selectedStorage == $variant->storage_id;
                                    $isDisabled =
                                        $this->selectedColor &&
                                        !$variations->contains(
                                            fn($v) => $v->storage_id == $variant->storage_id &&
                                                $v->color_id == $this->selectedColor,
                                        );
                                @endphp
                                <span
                                    class="badge bg-light text-dark py-2 px-3 storage-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                    wire:click="set('selectedStorage', {{ $variant->storage_id }})"
                                    aria-label="Pilih storage {{ $variant->storage->value }}"
                                    @if ($isDisabled) aria-disabled="true" @endif>
                                    {{ $variant->storage->value }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-sm fw-medium text-mono">
                                Rp{{ number_format($selectedVariant->rent_price, 0, ',', '.') }} /hari
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