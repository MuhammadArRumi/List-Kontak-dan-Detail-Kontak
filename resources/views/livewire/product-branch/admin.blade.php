<?php
use App\Models\Product;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use App\Models\Master\Attribute;
use function Livewire\Volt\{computed, state, usesPagination};
usesPagination(theme: 'bootstrap');

state([
    'branch' => '',
    'selectedProductId' => null,
    'selectedProductBranchId' => null,
    'selectedColor' => null,
    'selectedStorage' => null,
    'atribut' => null,
    'colors' => null,
    'storages' => null,
    'warna' => '',
    'penyimpanan' => '',
    'icloud' => '',
    'imei' => '',
    'harga_jual' => '',
    'editProductBranchId' => null,
    'editHargaSewa' => false,
    'editHargaJual' => false,
    'editIsPublish' => false,
]);
state([
    'search' => '',
])->url();

$branches = computed(function() {
    return Branch::where('st','a')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->prepend('Pilih Cabang', '');
});
$collection = computed(function() {
    // if (!$this->branch) return collect();
    $role = Auth::user()->getRoleNames()[0];
    $query = ProductBranch::with([
            'product.category', 
            'color',
            'storage',
            'branch'
    ]);
        // ->where('is_publish', 1);
    if($role == "Super Admin" || $role == "Owner"){
        $query->where('branch_id', $this->branch);
    }else{
        $query->where('branch_id', Auth::user()->branch_id);
    }

    return $query
        ->when($this->search, function($query) {
            $query->whereHas('product', function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        })
        ->get()
        ->groupBy('product_id');
});
$openAddStockModal = function($productId, $productBranchId) {
    $this->selectedProductId = $productId;
    $this->selectedProductBranchId = $productBranchId;
    $product = Product::find($productId);
    $this->atribut = $product->attributes->groupBy('title')->map(function ($items) {
        return collect($items);
    });
    $this->colors = $this->atribut['color'] ?? [];
    $this->storages = $this->atribut['storage'] ?? [];
};

$addStock = function() {
    $this->validate([
        'warna' => 'required',
        'penyimpanan' => 'required',
        'icloud' => 'required',
        'imei' => 'required',
    ]);
    if(Auth::user()->getRoleNames()[0] == "Super Admin" || Auth::user()->getRoleNames()[0] == "Owner"){
        $productBranch = ProductBranch::where('product_id', $this->selectedProductId)->where('branch_id', $this->branch)->first();
        $cabang = $this->branch;
    }else{
        $productBranch = ProductBranch::where('product_id', $this->selectedProductId)->where('branch_id', Auth::user()->branch_id)->first();
        $cabang = Auth::user()->branch_id;
    }
    ProductBranch::castAndCreate(
        [
            'product_id' => $this->selectedProductId,
            'branch_id' => $cabang,
            'color_id' => $this->warna,
            'storage_id' => $this->penyimpanan,
            'rent_price' => (float)($productBranch->rent_price ?: 0),
            'sale_price' => (float)($this->harga_jual),
            'icloud' => $this->icloud,
            'imei' => $this->imei,
            'is_publish' => true,
        ]
    );
    $this->dispatch('toast-success', message: "Branch Product saved successfully");
    return $this->redirect(route('product-branch'), navigate: true);
};
$openEditModal = function($productBranchId) {
    $this->editProductBranchId = $productBranchId;
    $productBranch = ProductBranch::find($productBranchId);
    $this->editIsPublish = $productBranch->is_publish;
    $this->editHargaSewa = $productBranch->rent_price;
    $this->editHargaJual = $productBranch->sale_price;
};

// Tambahkan method untuk menyimpan perubahan
$updatePublishStatus = function() {
    $product = ProductBranch::find($this->editProductBranchId);
    ProductBranch::where('product_id', $product->product_id)
        ->where('color_id', $product->color_id)
        ->where('storage_id', $product->storage_id)
        ->update([
            'rent_price' => (float)($this->editHargaSewa ?: $product->rent_price),
            'sale_price' => (float)($this->editHargaJual ?: $product->sale_price),
            'is_publish' => $this->editIsPublish
        ]);
    
    $this->dispatch('toast-success', message: "Produk berhasil diupdate");
};
?>
<div>
     <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush mb-7">
                <div class="card-body pt-5">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 gap-md-6 py-3">
                        <!-- Title Section -->
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                 <h2 class="text-primary">Katalog Produk</h2>
                                </div>
                            </div>
                            @if($this->search)
                                <span class="text-muted fs-6 mt-2">
                                    untuk pencarian "<span class="fw-semibold">{{ $this->search }}</span>"
                                </span>
                            @else
                                <span class="text-muted fs-6 mt-2">
                                    Kelola Produk Cabang Anda dengan mudah
                                </span>
                            @endif
                        </div>
                        <!-- Controls Section -->
                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-end">
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                               <input 
                    type="search" 
                    wire:model.live="search" 
                    class="form-control" 
                    placeholder="Cari produk..." 
                    aria-label="Search categories"
                >
                            </div>
                            <!-- Status Filter -->
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-250px">
                                  @role("Super Admin|Owner")
                <x-form-select name="branch" modifier="live" 
                    class="form-select" 
                    :options="$this->branches"
                    aria-label="Pilih cabang"/>
                @endrole
                            </div>
                            <!-- Add Button - Now with consistent width -->
                            <a href="{{ route('product-branch.create') }}" wire:navigate
                                class="btn btn-light-primary w-50 d-flex align-items-center justify-content-center px-3 rounded-1 hover-elevate-up"
                                aria-label="Add new category">
                                <i class="ki-outline ki-plus fs-5 me-2"></i>
                                <span class="text-nowrap">Tambah</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    @if($this->collection->count() > 0)
    <div class="row g-4" id="products">
        @foreach($this->collection as $productId => $variations)
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
            $colorImage = null;
            if ($selectedVariant->color) {
                $colorImage = asset('storage/product/' . $product->slug . '-' . Str::slug($selectedVariant->color->value) . '.png');
            }
            $cabang = Branch::where('id', $this->branch)->first();
        @endphp
        
        <div class="col-6 col-md-4 col-lg-3 mb-5" wire:key="product-{{ $productId }}-{{ $this->selectedColor }}-{{ $this->selectedStorage }}">
            <div class="card card-product h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Badge Stok -->
                @if($totalStock > 0)
                <div class="position-absolute top-0 start-0 m-3">
                    <span class="badge bg-success bg-opacity-90 text-white py-2 px-3 fw-bold rounded-pill">
                        Stok: {{ $totalStock }}
                    </span>
                </div>
                @endif
                
                <!-- Product Image -->
                <div class="product-image-container position-relative overflow-hidden d-flex align-items-center justify-content-center">
                    <img src="{{ $colorImage ?? $product->image }}" 
                        class="img-fluid object-fit-contain p-4" 
                        alt="{{ $product->name }}"
                        style="max-height: 180px;"
                        loading="lazy"
                        wire:loading.class.delay="opacity-50">
                        
                    <!-- Loading indicator -->
                    <div wire:loading.delay class="position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4" wire:ignore.self>
                    <!-- Category -->
                    <div class="mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary fs-7 fw-semibold rounded-pill py-2">
                            {{ $product->category->name ?? 'No Category' }}
                        </span>
                    </div>
                    
                    <!-- Product Name -->
                    <h3 class="mb-0 fs-5 fw-bold text-gray-900 truncate-2" style="min-height: 48px;">
                        {{ $product->name }}
                    </h3>
                    
                    <!-- Price Display -->
                    <div class="price-display mb-3">
                        @if($selectedVariant->sale_price > 0)
                            <!-- Rental Price Section -->
                            <div class="rental-price mb-3">
                                <div class="d-flex align-items-baseline">
                                    <span class="fs-3 fw-bold text-primary me-2">
                                        Rp{{ number_format($selectedVariant->rent_price, 0, ',', '.') }}
                                    </span>
                                    <span class="text-muted fs-6">/hari</span>
                                </div>
                            </div>
                        @else
                            <!-- Rental Only Section -->
                            <div class="rental-price">
                                <div class="d-flex align-items-baseline">
                                    <span class="fs-3 fw-bold text-primary me-2">
                                        Rp{{ number_format($selectedVariant->rent_price, 0, ',', '.') }}
                                    </span>
                                    <span class="text-muted fs-6">/hari</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Rating & Location -->
                    <div class="d-flex align-items-center text-muted fs-7 mb-4">
                        <span class="me-3">
                            <i class="ki-solid ki-star text-warning me-1"></i>
                            {{ number_format($product->averageRating()) }}
                            @if($product->ratings->count() > 0)
                                <span class="text-muted">({{ $product->ratings->count() }})</span>
                            @endif
                        </span>
                    </div>
                    
                    <!-- Color Options -->
                    @if($uniqueColors->count() > 1)
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-2">Warna</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($uniqueColors as $variant)
                                @if($variant->color)
                                @php
                                $colorValue = match($variant->color->value) {
                                    // iPhone 7 Series
                                    "Black" => '#000000',
                                    "Silver" => '#C0C0C0',
                                    "Gold" => '#FFD700',
                                    "Rose Gold" => '#B76E79',
                                    "Jet Black" => '#0A0A0A',
                                    "Red" => '#FF0000',
                                    
                                    // iPhone 8/X Series
                                    "Space Gray" => '#535150',
                                    
                                    // iPhone XR
                                    "White" => '#FFFFFF',
                                    "Blue" => '#007AFF',
                                    "Yellow" => '#FFD60A',
                                    "Coral" => '#FF7E79',
                                    
                                    // iPhone 11 Series
                                    "Green" => '#A7D3A6',
                                    "Purple" => '#D1B3FF',
                                    "Midnight Green" => '#475C4D',
                                    
                                    // iPhone 12 Series
                                    "Pacific Blue" => '#2D5F7A',
                                    "Graphite" => '#4F4F4F',
                                    
                                    // iPhone 13 Series
                                    "Midnight" => '#000000',
                                    "Starlight" => '#F8F9F0',
                                    "Pink" => '#FFB6C1',
                                    "Sierra Blue" => '#9BB5CE',
                                    
                                    // iPhone 14 Series
                                    "Deep Purple" => '#4A3C5F',
                                    "Space Black" => '#333333',
                                    
                                    // iPhone 15 Series
                                    "Black Titanium" => '#2D2D2D',
                                    "White Titanium" => '#E5E4E2',
                                    "Blue Titanium" => '#5E7E9B',
                                    "Natural Titanium" => '#8B8B8B',
                                    
                                    // iPhone 16 Series (Prediksi)
                                    "Deep Blue" => '#003366',
                                    // Samsung A50s
                                    "Prism Crush Black" => '#000000',
                                    "Prism Crush White" => '#FFFFFF',
                                    "Prism Crush Green" => '#C5E3B1',
                                    "Prism Crush Violet" => '#C6B5D6',
                                    
                                    // Samsung S22 Ultra
                                    "Phantom Black" => '#2D2926',
                                    "Phantom White" => '#EAE7DE',
                                    "Green" => '#A7D3A6',
                                    "Burgundy" => '#800020',
                                    "Graphite" => '#4F4F4F',
                                    "Sky Blue" => '#87CEEB',
                                    "Red" => '#FF0000',
                                    
                                    // Samsung S23 Ultra
                                    "Cream" => '#F5F5DC',
                                    "Lavender" => '#E6E6FA',
                                    "Lime" => '#BFFF00',
                                    
                                    // Samsung S24 Ultra
                                    "Titanium Black" => '#2B2B2B',
                                    "Titanium Gray" => '#8E8E8E',
                                    "Titanium Violet" => '#B399D4',
                                    "Titanium Yellow" => '#FFD700',
                                    "Titanium Blue" => '#4682B4',
                                    "Titanium Green" => '#2E8B57',
                                    "Titanium Orange" => '#FF8C00',
                                    
                                    // Samsung S25 Ultra (Prediksi)
                                    "Onyx Black" => '#0F0F0F',
                                    "Marble White" => '#F2F0EB',
                                    "Cobalt Violet" => '#8A2BE2',
                                    "Amber Yellow" => '#FFBF00',
                                    "Sapphire Blue" => '#0F52BA',
                                    "Emerald Green" => '#50C878',
                                    
                                    // Google Pixel
                                    "Obsidian" => '#0B1215',
                                    "Hazel" => '#8E7618',
                                    "Rose" => '#FF007F',
                                    "Snow" => '#FFFAFA',
                                    default => $attribute->value
                                };
                                $isActive = $this->selectedColor == $variant->color_id;
                                $isDisabled = $this->selectedStorage && !$variations->contains(fn($v) => 
                                    $v->color_id == $variant->color_id && 
                                    $v->storage_id == $this->selectedStorage
                                );
                                @endphp
                                <div 
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $variant->color->value }}"
                                    class="color-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}" 
                                    style="background-color: {{ $colorValue }};"
                                    wire:click="set('selectedColor', {{ $variant->color_id }})"
                                    aria-label="Pilih warna {{ $variant->color->value }}"
                                    @if($isDisabled) aria-disabled="true" @endif
                                ></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <!-- Storage Options -->
                    @if($uniqueStorages->count() > 1)
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-block mb-2">Storage</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($uniqueStorages as $variant)
                                @php
                                    $isActive = $this->selectedStorage == $variant->storage_id;
                                    $isDisabled = $this->selectedColor && !$variations->contains(fn($v) => 
                                        $v->storage_id == $variant->storage_id && 
                                        $v->color_id == $this->selectedColor
                                    );
                                @endphp
                                <span 
                                    class="badge bg-light text-dark py-2 px-3 storage-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
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
                    <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <div class="d-flex gap-2">
                            <button onclick="toggleStockModal('show');" wire:click="openAddStockModal('{{ $product->id }}', '{{ $selectedVariant->id }}')" class="btn btn-icon btn-light-success rounded-pill fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah Stok">
                                <i class="ki-filled ki-plus fs-3"></i> 
                            </button>
                            <!-- Ganti tombol edit yang ada dengan ini -->
                            <button onclick="togglePublishModal('show');" 
                                wire:click="openEditModal('{{ $selectedVariant->id }}')" 
                                class="btn btn-icon btn-light-warning rounded-pill fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center" 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                title="Ubah Status Publikasi">
                                <i class="ki-filled ki-notepad-edit fs-3"></i> 
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        <div class="modal fade" wire:ignore.self id="addStockModal" tabindex="-1" aria-hidden="true" wire:model="showStockModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Stok Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($selectedProductBranchId)
                            @php
                                $productBranch = ProductBranch::with(['product', 'color', 'storage', 'branch'])->find($selectedProductBranchId);
                                $stok = ProductBranch::where('product_id', $productBranch->product_id)->count();
                                $product = $productBranch->product;
                            @endphp
                            
                            <div class="mb-5">
                                <div class="d-flex align-items-center mb-4">
                                    @if($product->thumbnail)
                                        <div class="symbol symbol-50px me-5">
                                            <img src="{{ Storage::url($product->thumbnail) }}" class="" alt="{{ $product->name }}">
                                        </div>
                                    @endif
                                    <div>
                                        <h5 class="fw-bold mb-1">{{ $product->name }}</h5>
                                        <div class="text-muted">{{ $productBranch->branch->name }}</div>
                                    </div>
                                </div>
                                
                                <!-- Color Selection -->
                                @if($uniqueColors->count() > 0)
                                <div class="mb-4">
                                    <label class="form-label">Warna</label>
                                    <select class="form-select form-select-solid" wire:model="warna">
                                        <option value="">Pilih Warna</option>
                                        @foreach($this->colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('warna') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                @endif
                                
                                <!-- Storage Selection -->
                                @if($uniqueStorages->count() > 0)
                                <div class="mb-4">
                                    <label class="form-label">Storage</label>
                                    <select class="form-select form-select-solid" wire:model="penyimpanan">
                                        <option value="">Pilih Storage</option>
                                        @foreach($this->storages as $storage)
                                            <option value="{{ $storage->id }}">{{ $storage->value }}</option>
                                        @endforeach
                                    </select>
                                    @error('penyimpanan') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                @endif
                                
                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <x-form-group name="icloud" label="iCloud">
                                            <x-form-input type="text" name="icloud" placeholder="iCloud" />
                                        </x-form-group>
                                    </div>

                                    <div class="col-md-6">
                                        <x-form-group name="imei" label="IMEI">
                                            <x-form-input type="text" name="imei" placeholder="IMEI" />
                                        </x-form-group>
                                    </div>
                                </div>
                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <x-form-group name="harga_jual" label="Harga Jual">
                                            <x-form-input type="text" name="harga_jual" placeholder="Harga Jual" />
                                        </x-form-group>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close">
                            Batal
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="addStock" wire:loading.attr="disabled">
                            <span wire:loading.remove>Tambah Stok</span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Publish Status Modal -->
        <div class="modal fade" wire:ignore.self id="editPublishModal" tabindex="-1" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Status Publikasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($editProductBranchId)
                            @php
                                $productBranch = ProductBranch::with('product')->find($editProductBranchId);
                            @endphp
                            <div class="mb-5">
                                <div class="d-flex align-items-center mb-4">
                                    @if($productBranch->product->thumbnail)
                                        <div class="symbol symbol-50px me-5">
                                            <img src="{{ Storage::url('storage/product'.$productBranch->product->thumbnail) }}" class="" alt="{{ $productBranch->product->name }}">
                                        </div>
                                    @endif
                                    <div>
                                        <h5 class="fw-bold mb-1">{{ $productBranch->product->name }}</h5>
                                        <div class="text-muted">{{ $productBranch->branch->name }}</div>
                                    </div>
                                </div>

                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <x-form-group name="editHargaSewa" label="Harga Sewa">
                                            <x-form-input type="text" name="editHargaSewa" placeholder="Harga Sewa" />
                                        </x-form-group>
                                    </div>
                                    <div class="col-md-6">
                                        <x-form-group name="editHargaJual" label="Harga Jual">
                                            <x-form-input type="text" name="editHargaJual" placeholder="Harga Jual" />
                                        </x-form-group>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="is_publish" 
                                        wire:model="editIsPublish" {{ $this->editIsPublish ? 'checked' : '' }} style="width: 40px; height: 20px;" />
                                    <label class="form-check-label" for="is_publish">
                                        <span class="fw-bold">{{ $this->editIsPublish ? 'Dipublikasikan' : 'Tidak Dipublikasikan' }}</span>
                                        <span class="text-muted d-block">Produk akan {{ $editIsPublish ? 'ditampilkan' : 'disembunyikan' }} di katalog</span>
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close">
                            Batal
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="updatePublishStatus" wire:loading.attr="disabled">
                            <span wire:loading.remove>Simpan Perubahan</span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-15 mb-5">
        <img src="{{ asset('assets/media/illustrations/there-is-nothing-here.png') }}" 
                class="w-200px mb-5 theme-light-show" 
                alt="No Products"
                loading="lazy">
        <img src="{{ asset('assets/media/illustrations/there-is-nothing-here-dark.png') }}" 
                class="w-200px mb-5 theme-dark-show" 
                alt="No Products"
                loading="lazy">
        <h3 class="text-gray-600">Produk tidak ditemukan</h3>
        <p class="text-muted">Silakan cari dengan kata kunci lain atau pilih cabang berbeda</p>
        <button class="btn btn-primary rounded-pill px-6" wire:click="$set('search', '')">
            Reset Pencarian
        </button>
    </div>
    @endif
    @section('custom_js')
    <script data-navigate-once>
        // Tambahkan di bagian custom_js
        function togglePublishModal(value) {
            const modal = new bootstrap.Modal(document.getElementById('editPublishModal'));
            if (value == "show") {
                modal.show();
            } else {
                modal.hide();
            }
        }
        function toggleStockModal(value){
            const modal = new bootstrap.Modal(document.getElementById('addStockModal'));
            if (value == "show") {
                modal.show();
            } else {
                modal.hide();
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            // Handle variant selection animation
            Livewire.on('variant-selected', () => {
                const card = document.querySelector('.card-product:hover');
                if (card) {
                    card.classList.add('variant-selected');
                    setTimeout(() => {
                        card.classList.remove('variant-selected');
                    }, 300);
                }
            });
        });
        document.addEventListener('livewire:navigated', () => {
            // Handle variant selection animation
            Livewire.on('variant-selected', () => {
                const card = document.querySelector('.card-product:hover');
                if (card) {
                    card.classList.add('variant-selected');
                    setTimeout(() => {
                        card.classList.remove('variant-selected');
                    }, 300);
                }
            });
        });
    </script>
    @endsection
        </div>
</div>
</div>