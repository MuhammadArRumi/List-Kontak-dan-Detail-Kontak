<?php
use App\Models\Product;
use App\Models\Transaction\Rent;
use App\Models\Master\Brand;
use App\Models\Master\Category;
use function Livewire\Volt\{computed, state, usesPagination};

usesPagination(theme: 'bootstrap');

state([
    'search' => '',
    'brand_id' => null,
    'category_id' => null
])->url();

state(['sortColumn' => '', 'sortDirection' => 'ASC']);

state(['deleteId' => null]);

$sort = function ($columnName) {
    if ($this->sortColumn === $columnName) {
        $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
    } else {
        $this->sortColumn = $columnName;
        $this->sortDirection = 'ASC';
    }
};

$totalProducts = computed(function () {
    return Product::query()
        ->with(['brand', 'category'])
        ->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('slug', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%');
        })
        ->when($this->brand_id, function ($query) {
            $query->where('brand_id', $this->brand_id);
        })
        ->when($this->category_id, function ($query) {
            $query->where('category_id', $this->category_id);
        })
        ->count();
});

$totalRent = computed(function () {
    return Rent::query()
        ->whereIn('status', ['completed', 'active'])
        ->count();
});

$stockRent = computed(function() {
    $products = Product::count();
    $totalRent = Rent::query()
        ->whereIn('status', ['completed', 'active'])
        ->count();
    return $products - $totalRent;
});

$products = computed(function () {
    return Product::query()
        ->with(['brand', 'category'])
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->brand_id, function ($query) {
            $query->where('brand_id', $this->brand_id);
        })
        ->when($this->category_id, function ($query) {
            $query->where('category_id', $this->category_id);
        })
        ->when($this->sortColumn, function ($query) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }, function ($query) {
            $query->orderBy('id', 'DESC');
        })
        ->paginate(10);
});

$brands = computed(fn() => Brand::where('st', 'a')->orderBy('name')->get());
$categories = computed(fn() => Category::where('st', 'a')->orderBy('name')->get());

$deleteProduct = function ($id) {
    $product = Product::find($id);
    if ($product) {
        $product->delete();
        session()->flash('success', 'Produk berhasil dihapus!');
    }
};
?>

<div>
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush mb-7">
                <div class="card-body pt-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 gap-md-6 py-3">
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <h2 class="h3 fw-bold mb-0">
                                        <span class="text-primary">{{ $this->totalProducts }}</span> Produk Ditemukan
                                    </h2>
                                </div>
                            </div>
                            @if($this->search)
                                <span class="text-muted fs-6 mt-2">untuk pencarian "<span class="fw-semibold">{{ $this->search }}</span>"</span>
                            @else
                                <span class="text-muted fs-6 mt-2">Kelola produk Anda dengan mudah</span>
                            @endif
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-end">
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <input type="search" wire:model.live="search" class="form-control ps-5" placeholder="Cari Produk...">
                            </div>
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <select class="form-select" wire:model.live="brand_id">
                                    <option value="">Semua Merk</option>
                                    @foreach($this->brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <select class="form-select" wire:model.live="category_id">
                                    <option value="">Semua Kategori</option>
                                    @foreach($this->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <a href="{{ route('product.create') }}" wire:navigate
                                class="btn btn-light-primary w-50 d-flex align-items-center justify-content-center px-3 rounded-1 hover-elevate-up"
                                aria-label="Add new product">
                                <i class="ki-outline ki-plus fs-5 me-2"></i>
                                <span class="text-nowrap">Tambah</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 mb-5">
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-primary card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fs-2hx fw-bold text-primary me-2">{{ Product::count() }}</span>
                                </div>
                                <span class="text-gray-600">Total Produk</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-primary card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fs-2hx fw-bold text-primary me-2">{{ $this->totalRent }}</span>
                                </div>
                                <span class="text-gray-600">Dalam Sewaan</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-primary card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fs-2hx fw-bold text-primary me-2">{{ $this->stockRent }}</span>
                                </div>
                                <span class="text-gray-600">Total Stock Tersedia</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-info card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fs-2hx fw-bold text-info me-2">{{ $this->totalProducts }}</span>
                                </div>
                                <span class="text-gray-600">Hasil Filter</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 table-row-gray-300 gy-7">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-150px cursor-pointer" wire:click="sort('name')">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>Product</span>
                                            @if($sortColumn === 'name')
                                                <i class="ki-outline ki-arrow-{{ $sortDirection === 'ASC' ? 'up' : 'down' }} fs-3 text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="min-w-100px">Brand</th>
                                    <th class="min-w-100px">Category</th>
                                    <th class="min-w-100px text-center">Created At</th>
                                    <th class="min-w-100px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse ($this->products as $product)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="img-thumbnail me-3">
                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="{{ $product->name }}" width="50" height="50" style="object-fit: cover;">
                                                </div>
                                                <div>
                                                    <span class="text-gray-800 fw-bold d-block">{{ $product->name }}</span>
                                                    <small class="text-muted">{{ $product->slug }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $product->brand->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ $product->category->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">{{ $product->created_at->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a wire:navigate href="{{ route('product.show', ['product' => $product]) }}" class="btn btn-icon btn-sm btn-light-primary" data-bs-toggle="tooltip" title="View">
                                                    <i class="ki-outline ki-eye fs-2"></i>
                                                </a>
                                                <a wire:navigate href="{{ route('product.edit', ['product' => $product]) }}" class="btn btn-icon btn-sm btn-light-warning" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="ki-outline ki-notepad-edit fs-2"></i>
                                                </a>
                                                <button type="button" class="btn btn-icon btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" wire:click="$set('deleteId', {{ $product->id }})" title="Delete">
                                                    <i class="ki-outline ki-trash fs-2"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-10">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ki-outline ki-search-list fs-4x text-muted mb-4"></i>
                                                <span class="text-gray-600 fs-6">Tidak ada produk yang ditemukan</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($this->products->hasPages())
                        <div class="card-footer">{{ $this->products->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="modal fade" tabindex="-1" id="deleteModal" wire:ignore.self>
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">Hapus Produk</h3>
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </div>
                        </div>
                        <div class="modal-body">
                            <p>Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" wire:click="deleteProduct({{ $deleteId ?? 0 }})" data-bs-dismiss="modal">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
