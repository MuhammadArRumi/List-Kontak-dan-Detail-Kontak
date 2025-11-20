<?php
use function Livewire\Volt\{mount, state, rules, usesFileUploads};

// Add file upload support
usesFileUploads();

state(['promo']);
state(
    [
        'name',
        'code',
        'description',
        'type',
        'thumbnail',
        'value',
        'buy_quantity',
        'get_quantity',
        'free_product_id',
        'min_order_amount',
        'max_uses',
        'max_uses_per_user',
        'start_date',
        'end_date',
        'scope',
        'day_restriction',
        'applicable_for',
        'is_active',
        'selectedProduct',
        'selectedCategory',
        'selectedBranch',
        'products' => [],
        'categories' => [],
        'branches' => []
    ]
);

rules(fn() => [
    'name' => 'required|string|max:255',
    'code' => 'required|string|max:255|unique:promos,code' . ($this->promo ? ',' . $this->promo->id : ''),
    'description' => 'nullable|string',
    'type' => 'required|string|in:percentage,fixed_amount,b1g1,free_shipping',
    'thumbnail' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
    'value' => 'nullable|required_if:type,percentage|numeric|min:0',
    'buy_quantity' => 'nullable|integer|min:1',
    'get_quantity' => 'nullable|integer|min:1',
    'free_product_id' => 'nullable|integer|exists:products,id',
    'min_order_amount' => 'nullable|numeric|min:0',
    'max_uses' => 'nullable|integer|min:1',
    'max_uses_per_user' => 'nullable|integer|min:1',
    'start_date' => 'required|date',
    'end_date' => 'required|date|after_or_equal:start_date',
    'scope' => 'required|string|in:products,categories,branches',
    'day_restriction' => 'required|string|in:all,weekday,weekend',
    'applicable_for' => 'required|string|in:all,rent,sale',
    'is_active' => 'required|boolean',
    'selectedProduct' => 'nullable|required_if:scope,product|exists:products,id',
    'selectedCategory' => 'nullable|required_if:scope,category|exists:categories,id',
    'selectedBranch' => 'nullable|required_if:scope,branch|exists:branches,id',
]);

mount(function () {
    $this->products = App\Models\Product::all();
    $this->categories = App\Models\Master\Category::all();
    $this->branches = App\Models\Master\Branch::all();

    // Set default values untuk form baru
    if ($this->promo) {

        // Load data dari existing promo
        $this->name = $this->promo->name;
        $this->code = $this->promo->code;
        $this->description = $this->promo->description;
        $this->type = $this->promo->type;
        $this->value = $this->promo->value;
        $this->buy_quantity = $this->promo->buy_quantity;
        $this->get_quantity = $this->promo->get_quantity;
        $this->free_product_id = $this->promo->free_product_id;
        $this->min_order_amount = $this->promo->min_order_amount;
        $this->max_uses = $this->promo->max_uses;
        $this->max_uses_per_user = $this->promo->max_uses_per_user;
        $this->start_date = $this->promo->start_date->format('Y-m-d\TH:i');
        $this->end_date = $this->promo->end_date->format('Y-m-d\TH:i');
        $this->scope = $this->promo->scope;
        $this->day_restriction = $this->promo->day_restriction;
        $this->applicable_for = $this->promo->applicable_for;
        $this->is_active = $this->promo->is_active;

        // Load existing relationships
        if ($this->promo->scope === 'product' && $this->promo->products->isNotEmpty()) {
            $this->selectedProduct = $this->promo->products->first()->id;
        } elseif ($this->promo->scope === 'category' && $this->promo->categories->isNotEmpty()) {
            $this->selectedCategory = $this->promo->categories->first()->id;
        } elseif ($this->promo->scope === 'branch' && $this->promo->branches->isNotEmpty()) {
            $this->selectedBranch = $this->promo->branches->first()->id;
        }
    }
});

$updatedScope = function ($value) {
    $this->reset(['selectedProduct', 'selectedCategory', 'selectedBranch']);
};

$save = function () {
    $this->validate();

    // Prepare data untuk tabel promos
    $data = [
        'name' => $this->name,
        'code' => $this->code,
        'description' => $this->description,
        'type' => $this->type,
        'value' => $this->value,
        'buy_quantity' => $this->buy_quantity,
        'get_quantity' => $this->get_quantity,
        'free_product_id' => $this->free_product_id,
        'min_order_amount' => $this->min_order_amount,
        'max_uses_per_user' => $this->max_uses_per_user,
        'max_uses' => $this->max_uses,
        'start_date' => $this->start_date,
        'end_date' => $this->end_date,
        'scope' => $this->scope,
        'day_restriction' => $this->day_restriction,
        'applicable_for' => $this->applicable_for,
        'is_active' => $this->is_active
    ];

    // Handle file upload
    if ($this->thumbnail) {
        $filename = $this->thumbnail->store('promos', 'public');
        $data['thumbnail'] = $filename;
    }

    // Create or update promo
    if ($this->promo) {
        // Update existing promo
        $this->promo->update($data);
        $promo = $this->promo;

        // Delete existing relationships for update
        if ($this->scope === 'products') {
            \App\Models\PromoProduct::where('promo_id', $promo->id)->delete();
        } elseif ($this->scope === 'categories') {
            \App\Models\PromoCategories::where('promo_id', $promo->id)->delete();
        } elseif ($this->scope === 'branches') {
            \App\Models\PromoBranches::where('promo_id', $promo->id)->delete();
        }

        session()->flash('message', 'Promo berhasil diperbarui!');
    } else {
        // Create new promo
        $promo = \App\Models\Promo::create($data);
        session()->flash('message', 'Promo berhasil dibuat!');
    }

    // Create relationship records based on scope
    if ($this->scope === 'products' && $this->selectedProduct) {
        \App\Models\PromoProduct::create([
            'promo_id' => $promo->id,
            'product_id' => $this->selectedProduct
        ]);
    } elseif ($this->scope === 'categories' && $this->selectedCategory) {
        \App\Models\PromoCategories::create([
            'promo_id' => $promo->id,
            'category_id' => $this->selectedCategory
        ]);
    } elseif ($this->scope === 'branches' && $this->selectedBranch) {
        \App\Models\PromoBranches::create([
            'promo_id' => $promo->id,
            'branch_id' => $this->selectedBranch
        ]);
    }

    return $this->redirect(route('promo'), navigate: true);
}

?>

<div>
    <!-- Flash Message -->
    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="d-flex flex-column flex-lg-row">
                <!-- Main Content -->
                <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
                    <div class="card">
                        <div class="card-body p-12">
                            <!-- Form with wire:submit -->
                            <form wire:submit="save" id="kt_invoice_form">
                                <div class="d-flex flex-column align-items-start flex-xxl-row">
                                    <div
                                        class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4">
                                        <span class="fs-2x fw-bold text-gray-800">
                                            @if ($this->promo)
                                                Edit Promo
                                            @else
                                                Tambah Promo Baru
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-10"></div>

                                <div class="mb-0">
                                    <!-- Row 1 -->
                                    <div class="row gx-10 mb-5">
                                        <div class="col-lg-6">
                                            <!-- Nama Promo -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Nama Promo<span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control form-control-solid @error('name') is-invalid @enderror"
                                                    placeholder="Nama Promo" wire:model="name" />
                                                @error('name') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Nama promo wajib diisi.</div>
                                            </div>

                                            <!-- Kode Promo -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Kode Promo<span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control form-control-solid @error('code') is-invalid @enderror"
                                                    placeholder="Kode Promo" wire:model="code" />
                                                @error('code') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Kode promo wajib diisi dan harus unik.</div>
                                            </div>

                                            <!-- Deskripsi -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Deskripsi Promo
                                                </label>
                                                <textarea
                                                    class="form-control form-control-solid @error('description') is-invalid @enderror"
                                                    rows="3" placeholder="Deskripsi Promo"
                                                    wire:model="description"></textarea>
                                                @error('description') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Tipe Promo -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Tipe Promo<span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    class="form-select form-select-solid @error('type') is-invalid @enderror"
                                                    wire:model="type">
                                                    <option value="">Pilih Tipe</option>
                                                    <option value="percentage">Persentase</option>
                                                    <option value="fixed_amount">Nominal Tetap</option>
                                                    <option value="b1g1">Beli X Dapat Y</option>
                                                    <option value="free_shipping">Gratis Ongkir</option>
                                                </select>
                                                @error('type') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Tipe promo wajib diisi.</div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <!-- Nilai Diskon -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Nilai Diskon<span class="text-danger">*</span>
                                                </label>
                                                <input type="number"
                                                    class="form-control form-control-solid @error('value') is-invalid @enderror"
                                                    placeholder="Nilai Diskon" wire:model="value" step="0.01" />
                                                @error('value') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Nilai diskon (untuk persentase: 0-100, untuk
                                                    nominal: nilai rupiah).</div>
                                            </div>

                                            <!-- Minimal Pembelian -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Minimal Pembelian
                                                </label>
                                                <input type="number"
                                                    class="form-control form-control-solid @error('buy_quantity') is-invalid @enderror"
                                                    placeholder="Minimal Pembelian" wire:model="buy_quantity" />
                                                @error('buy_quantity') <div class="invalid-feedback">{{ $message }}
                                                </div> @enderror
                                                <div class="form-text">Jumlah minimal pembelian sebagai syarat promo.
                                                </div>
                                            </div>

                                            <!-- Maksimal Penggunaan -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Maksimal Penggunaan
                                                </label>
                                                <input type="number"
                                                    class="form-control form-control-solid @error('max_uses') is-invalid @enderror"
                                                    placeholder="Maksimal Penggunaan" wire:model="max_uses" />
                                                @error('max_uses') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Jumlah maksimal penggunaan promo secara
                                                    keseluruhan.</div>
                                            </div>

                                            <!-- Maksimal Penggunaan Per User -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Maksimal Penggunaan Per User
                                                </label>
                                                <input type="number"
                                                    class="form-control form-control-solid @error('max_uses_per_user') is-invalid @enderror"
                                                    placeholder="Maksimal Penggunaan Per User"
                                                    wire:model="max_uses_per_user" />
                                                @error('max_uses_per_user') <div class="invalid-feedback">{{ $message }}
                                                </div> @enderror
                                                <div class="form-text">Jumlah maksimal penggunaan per user.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="separator separator-dashed my-10"></div>

                                    <!-- Row 2 -->
                                    <div class="row gx-10 mb-5">
                                        <div class="col-lg-6">
                                            <!-- Tanggal Mulai -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Tanggal Mulai<span class="text-danger">*</span>
                                                </label>
                                                <input type="datetime-local"
                                                    class="form-control form-control-solid @error('start_date') is-invalid @enderror"
                                                    wire:model="start_date" />
                                                @error('start_date') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Tanggal mulai promo berlaku.</div>
                                            </div>

                                            <!-- Tanggal Berakhir -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Tanggal Berakhir<span class="text-danger">*</span>
                                                </label>
                                                <input type="datetime-local"
                                                    class="form-control form-control-solid @error('end_date') is-invalid @enderror"
                                                    wire:model="end_date" />
                                                @error('end_date') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Tanggal berakhir promo.</div>
                                            </div>

                                            <!-- Cakupan Promo -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Cakupan Promo<span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    class="form-select form-select-solid @error('scope') is-invalid @enderror"
                                                    wire:model.live="scope" name="scope">
                                                    <option value="">Pilih Cakupan</option>
                                                    <option value="products">Produk Tertentu</option>
                                                    <option value="categories">Kategori Tertentu</option>
                                                    <option value="branches">Cabang Tertentu</option>
                                                </select>
                                                @error('scope') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Dynamic Select Based on Scope -->
                                            @if($scope === 'products')
                                                <div class="mb-5">
                                                    <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                        Pilih Produk<span class="text-danger">*</span>
                                                    </label>
                                                    <select
                                                        class="form-select form-select-solid @error('selectedProduct') is-invalid @enderror"
                                                        wire:model="selectedProduct">
                                                        <option value="">Pilih Produk</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('selectedProduct') <div class="invalid-feedback">{{ $message }}
                                                    </div> @enderror
                                                </div>
                                            @endif

                                            @if($scope === 'categories')
                                                <div class="mb-5">
                                                    <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                        Pilih Kategori<span class="text-danger">*</span>
                                                    </label>
                                                    <select
                                                        class="form-select form-select-solid @error('selectedCategory') is-invalid @enderror"
                                                        wire:model="selectedCategory">
                                                        <option value="">Pilih Kategori</option>
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('selectedCategory') <div class="invalid-feedback">{{ $message }}
                                                    </div> @enderror
                                                </div>
                                            @endif

                                            @if($scope === 'branches')
                                                <div class="mb-5">
                                                    <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                        Pilih Cabang<span class="text-danger">*</span>
                                                    </label>
                                                    <select
                                                        class="form-select form-select-solid @error('selectedBranch') is-invalid @enderror"
                                                        wire:model="selectedBranch">
                                                        <option value="">Pilih Cabang</option>
                                                        @foreach($branches as $branch)
                                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('selectedBranch') <div class="invalid-feedback">{{ $message }}
                                                    </div> @enderror
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-lg-6">
                                            <!-- Hari Berlaku -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Hari Berlaku<span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    class="form-select form-select-solid @error('day_restriction') is-invalid @enderror"
                                                    wire:model="day_restriction" name="day_restriction">
                                                    <option value="">Pilih Hari</option>
                                                    <option value="all">Semua Hari</option>
                                                    <option value="weekday">Hari Kerja</option>
                                                    <option value="weekend">Akhir Pekan</option>
                                                </select>
                                                @error('day_restriction') <div class="invalid-feedback">{{ $message }}
                                                </div> @enderror
                                            </div>

                                            <!-- Berlaku Untuk -->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">
                                                    Berlaku Untuk<span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    class="form-select form-select-solid @error('applicable_for') is-invalid @enderror"
                                                    wire:model="applicable_for" name="applicable_for">
                                                    <option value="">Pilih Tipe</option>
                                                    <option value="all">Semua (Sewa & Jual)</option>
                                                    <option value="rent">Sewa</option>
                                                    <option value="sale">Jual</option>
                                                </select>
                                                @error('applicable_for') <div class="invalid-feedback">{{ $message }}
                                                </div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="flex-lg-auto min-w-lg-300px">
                    <div class="card">
                        <div class="card-body p-10">
                            <!-- Thumbnail -->
                            <div class="mb-10 text-center">
                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">Thumbnail Promo</label>
                                <div class="text-center pt-0">
                                    <div class="image-input image-input-outline mb-3" data-kt-image-input="true">
                                        <div class="image-input-wrapper w-200px h-200px"
                                            style="background-image: url('{{ $this->thumbnail ? $this->thumbnail->temporaryUrl() : ($this->promo && $this->promo->thumbnail ? asset('storage/' . $this->promo->thumbnail) : asset('assets/media/svg/files/blank-image.svg')) }}')">
                                        </div>
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Ganti Thumbnail">
                                            <i class="ki-duotone ki-pencil fs-7">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <input type="file" wire:model="thumbnail" accept=".png, .jpg, .jpeg" />
                                        </label>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove"
                                            wire:click="$set('thumbnail', null)">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                    </div>
                                    @error('thumbnail') <div class="text-danger small">{{ $message }}</div> @enderror
                                    <div class="text-muted fs-7">
                                        Atur Thumbnail Promo. <br>
                                        Hanya format <span class="text-danger">*.png, *.jpg dan *.jpeg</span><br>
                                        yang diterima
                                    </div>
                                </div>
                            </div>

                            <div class="separator separator-dashed mb-8"></div>

                            <!-- is_active -->
                            <div class="mb-10">
                                <label class="form-label fw-bold fs-6 text-gray-700">is_active</label>
                                <select class="form-select @error('is_active') is-invalid @enderror"
                                    wire:model="is_active">
                                    <option value="">Pilih Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="separator separator-dashed mb-8"></div>

                            <!-- Submit Button -->
                            <div class="mb-0">
                                <button type="submit" class="btn btn-primary w-100" wire:click="save"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove>
                                        @if ($this->promo)
                                            Simpan Promo
                                        @else
                                            Tambah Promo
                                        @endif
                                    </span>
                                    <span wire:loading>Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>