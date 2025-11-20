<?php
use App\Models\Product;
use App\Models\Master\Brand;
use App\Models\Master\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{state, mount, usesFileUploads};

usesFileUploads();
state(['product']);
state([
    'productId' => null, // ID untuk edit mode
    'name' => '',
    'slug' => '',
    'code' => '',
    'thumbnail' => null,
    'existingThumbnail' => null, // Untuk menyimpan path thumbnail yang sudah ada
    'description_rent' => '',
    'brand_id' => '',
    'category_id' => '',
    'brands' => [],
    'categories' => [],
]);

mount(function () {
    $this->brands = Brand::where('st', 'a')->orderBy('name')->get();
    $this->categories = Category::where('st', 'a')->orderBy('name')->get();

    if ($this->product) {
        $this->productId = $this->product->id;
        $this->name = $this->product->name;
        $this->slug = $this->product->slug;
        $this->code = $this->product->code;
        $this->existingThumbnail = $this->product->thumbnail;
        $this->description_rent = $this->product->description_rent;
        $this->brand_id = $this->product->brand_id;
        $this->category_id = $this->product->category_id;
    }
});

$save = function () {
    $rules = [
        'name' => 'required|string|max:255',
        'slug' => [
            'required',
            'string',
            'max:255',
            'unique:products,slug' . ($this->productId ? ',' . $this->productId : '')
        ],
        'code' => [
            'required',
            'string',
            'max:50',
            'unique:products,code' . ($this->productId ? ',' . $this->productId : '')
        ],
        'brand_id' => 'required|exists:brands,id',
        'category_id' => 'required|exists:categories,id',
        'description_rent' => 'nullable|string',
    ];

    // Thumbnail required hanya saat create
    if ($this->productId) {
        $rules['thumbnail'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
    } else {
        $rules['thumbnail'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
    }

    $this->validate($rules);

    $data = [
        'name' => $this->name,
        'slug' => $this->slug,
        'code' => $this->code,
        'brand_id' => (int) $this->brand_id,
        'category_id' => (int) $this->category_id,
        'description_rent' => $this->description_rent,
    ];

    // Handle thumbnail upload
    if ($this->thumbnail) {
        // Hapus thumbnail lama jika ada saat update
        if ($this->productId && $this->existingThumbnail) {
            Storage::disk('public')->delete($this->existingThumbnail);
        }
        // Pastikan path penyimpanan sama dengan category jika ingin konsisten,
        // namun disini menggunakan 'product' yang sudah ada di logika product.
        $data['thumbnail'] = $this->thumbnail->store('product', 'public');
    } elseif ($this->productId) {
        // Pertahankan thumbnail lama jika tidak ada upload baru
        $data['thumbnail'] = $this->existingThumbnail;
    }

    if ($this->productId) {
        // Update mode
        $product = Product::findOrFail($this->productId);
        $product->castAndUpdate($data);
        session()->flash('success', 'Produk berhasil diperbarui!');
    } else {
        // Create mode
        Product::castAndCreate($data);
        session()->flash('success', 'Produk berhasil dibuat!');
    }

    return $this->redirect(route('product'), navigate: true);
};

$updatedName = function ($value) {
    // Auto-generate slug hanya saat create
    if (!$this->productId) {
        $this->slug = Str::slug($value);
    }
};

// Method untuk menghapus thumbnail
$removeThumbnail = function () {
    if ($this->productId && $this->existingThumbnail) {
        Storage::disk('public')->delete($this->existingThumbnail);
        $product = Product::findOrFail($this->productId);
        $product->update(['thumbnail' => null]);
        $this->existingThumbnail = null;
        session()->flash('success', 'Thumbnail berhasil dihapus!');
    }
    $this->thumbnail = null;
};
?>

<div>
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            @if(session()->has('success'))
                <div class="alert alert-success d-flex align-items-center mb-8">
                    <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-success">Berhasil!</h4>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <form wire:submit="save" class="form d-flex flex-column flex-lg-row">
                <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>Thumbnail {{ $productId ? '' : '(Required)' }}</h2>
                            </div>
                        </div>
                        <div class="card-body text-center pt-0">
                            <div class="image-input image-input-outline" data-kt-image-input="true"
                                style="background-image: url({{ asset('assets/media/svg/avatars/blank.svg') }})">
                                <div class="image-input-wrapper w-125px h-125px"
                                    wire:loading.class="opacity-50"
                                    style="background-image: url(
                                        @if($thumbnail)
                                            {{ $thumbnail->temporaryUrl() }}
                                        @elseif($existingThumbnail)
                                            {{ Storage::url($existingThumbnail) }}
                                        @else
                                            {{ asset('assets/media/svg/avatars/blank.svg') }}
                                        @endif
                                    ); background-size: cover;">
                                </div>
                                <label
                                    class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                    title="Pilih Foto">
                                    <i class="ki-filled ki-pencil fs-6"></i>
                                    <input type="file" wire:model="thumbnail" accept=".png,.jpg,.jpeg" />
                                </label>
                                @if($thumbnail || $existingThumbnail)
                                    <span
                                        class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                        wire:click="removeThumbnail" title="Hapus">
                                        <i class="ki-filled ki-trash fs-3"></i>
                                    </span>
                                @else
                                    <span
                                        class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                        title="Batalkan">
                                        <i class="ki-filled ki-cross fs-3"></i>
                                    </span>
                                @endif
                            </div>
                            <div class="text-muted fs-7 mt-3">Ukuran file maksimum untuk thumbnail adalah 2 MB. Format file : JPG, JPEG, atau PNG.</div>
                            @error('thumbnail')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>Klasifikasi</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-5">
                                <label class="form-label required fs-6 fw-semibold text-gray-700">Merk</label>
                                <select wire:model.defer="brand_id"
                                    class="form-select form-select-solid fw-bold" data-control="select2"
                                    data-placeholder="Pilih Merk" data-allow-clear="true" data-hide-search="true">
                                    <option value="">Pilih Merk</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <div class="text-muted fs-7">Tetapkan merk produk.</div>
                                @error('brand_id')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                            </div>

                            <div class="pb-14">
                                <label class="form-label required fs-6 fw-semibold text-gray-700">Kategori</label>
                                <select wire:model.defer="category_id"
                                    class="form-select form-select-solid fw-bold" data-control="select2"
                                    data-placeholder="Pilih Kategori" data-allow-clear="true" data-hide-search="true">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="text-muted fs-7">Tetapkan kategori produk.</div>
                                @error('category_id')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                </div>

                <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>Info Umum</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-10 fv-row">
                                <label class="form-label required">Nama Produk</label>
                                <input type="text" wire:model.live="name" class="form-control mb-2"
                                    placeholder="Nama Produk" autocomplete="off" />
                                <div class="text-muted fs-7">Nama produk wajib diisi dan sebaiknya unik.</div>
                                @error('name')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                            </div>

                            <div class="row mb-10 g-5">
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Kode Produk</label>
                                    <input type="text" wire:model.defer="code" class="form-control mb-2"
                                        placeholder="PRD-0001" autocomplete="off" />
                                    <div class="text-muted fs-7">Kode produk wajib diisi dan unik.</div>
                                    @error('code')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Slug</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light-secondary border-0 text-gray-500">adamasanya.test/product/</span>
                                        <input type="text" wire:model.defer="slug" class="form-control"
                                            placeholder="slug-produk" autocomplete="off" {{ $productId ? '' : 'readonly' }}>
                                    </div>
                                    <div class="form-text text-muted fs-7">
                                        {{ $productId ? 'Slug dapat disesuaikan saat edit.' : 'Slug dibuat otomatis dari nama produk.' }}
                                    </div>
                                    @error('slug')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>Deskripsi Produk</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="fv-row">
                                <label class="form-label">Deskripsi Sewa</label>
                                <textarea wire:model.defer="description_rent" class="form-control mb-2" rows="5"
                                    placeholder="Ringkas keunggulan utama atau syarat sewa."></textarea>
                                <div class="text-muted fs-7">Deskripsi ini akan tampil di halaman produk.</div>
                                @error('description_rent')<span class="text-danger d-block mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('product') }}" wire:navigate class="btn btn-light me-5">Batalkan</a>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span class="indicator-label fw-bold">{{ $productId ? 'Update' : 'Simpan' }} Produk</span>
                            <span class="indicator-progress">{{ $productId ? 'Mengupdate' : 'Menyimpan' }}...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>