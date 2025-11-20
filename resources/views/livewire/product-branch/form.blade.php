<?php
use App\Models\Product;
use App\Models\Master\Brand;
use App\Models\Master\Branch;
use App\Models\ProductBranch;
use App\Models\Master\Category;
use function Livewire\Volt\{computed, state, rules};

state([
    'kategori' => '',
    'merek' => '',
    'produk' => '',
    'cabang' => '',
    'warna' => '',
    'penyimpanan' => '',
    'harga_sewa' => '',
    'harga_jual' => '',
    'icloud' => '',
    'imei' => '',
    'atribut' => '',
    'colors' => [],
    'storages' => [],
]);

rules(fn() => [
    'kategori' => 'required',
    'merek' => 'required',
    'produk' => 'required',
    'cabang' => 'nullable',
    'warna' => 'required',
    'penyimpanan' => 'required',
    'harga_sewa' => 'required|numeric|min:0',
    'harga_jual' => 'nullable|numeric|min:0',
    'icloud' => 'required',
    'imei' => 'required'
]);
$brands = computed(function () {
    return Brand::where('st', 'a')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->prepend('Pilih Merek', '');
});
$category = computed(function () {
    return Category::where('st', 'a')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->prepend('Pilih Kategori', '');
});
$branches = computed(function () {
    return Branch::where('st', 'a')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->prepend('Pilih Cabang', '');
});
$updatedKategori = function () {
    // 
};
$updatedMerek = function () {
    //  
};
$updatedProduk = function () {
    $produk = Product::find($this->produk);
    $this->atribut = $produk->attributes->groupBy('title')->map(function ($items) {
        return collect($items);
    });
    $this->colors = $this->atribut['color']->pluck('value', 'id')->prepend('Pilih Warna', '') ?? [];
    $this->storages = $this->atribut['storage']->pluck('value', 'id')->prepend('Pilih Penyimpanan', '') ?? [];
};
$save = function () {
    $this->validate();
    $role = Auth::user()->getRoleNames()[0];
    if ($role == "Super Admin" || $role == "Owner") {
        $cabang = $this->cabang;
    } else {
        $cabang = Auth::user()->branch_id;
    }
    ProductBranch::castAndCreate([
        'product_id' => $this->produk,
        'branch_id' => $cabang,
        'color_id' => $this->warna,
        'storage_id' => $this->penyimpanan,
        'rent_price' => (float) ($this->harga_sewa ?: 0), // Handles empty string and null
        'sale_price' => (float) ($this->harga_jual ?: 0), // Converts empty string to 0
        'icloud' => $this->icloud,
        'imei' => $this->imei,
        'is_publish' => true,
    ]);

    $this->dispatch('toast-success', message: 'Produk berhasil ditambahkan');
    return $this->redirect(route('product-branch'), navigate: true);
};
?>
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card shadow-sm mb-5">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="row g-5 mb-5">
                        @php
                            $isAdmin = auth()->user()->hasRole(['Super Admin', 'Owner']);
                            $columnClass = $isAdmin ? 'col-md-3' : 'col-md-4';
                        @endphp

                        @if($isAdmin)
                            <div class="col-md-3">
                                <x-form-group name="cabang" label="Cabang">
                                    <x-form-select class="form-select form-select-solid" modifier="live" name="cabang"
                                        :options="$this->branches" />
                                </x-form-group>
                            </div>
                        @endif

                        <div class="{{ $columnClass }}">
                            <x-form-group name="kategori" label="Kategori">
                                <x-form-select class="form-select form-select-solid" modifier="live" name="kategori"
                                    :options="$this->category" />
                            </x-form-group>
                        </div>

                        <div class="{{ $columnClass }}">
                            <x-form-group name="merek" label="Merek">
                                <x-form-select class="form-select form-select-solid" modifier="live" name="merek"
                                    :options="$this->brands" />
                            </x-form-group>
                        </div>

                        <div class="{{ $columnClass }}">
                            <x-form-group name="produk" label="Produk">
                                @php
                                    $produkOptions = Product::query()
                                        ->where('category_id', $this->kategori)
                                        ->where('brand_id', $this->merek)
                                        ->pluck('name', 'id')
                                        ->prepend('Pilih Produk', '');
                                @endphp
                                <x-form-select class="form-select form-select-solid" modifier="live" name="produk"
                                    :options="$produkOptions" />
                            </x-form-group>
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        <!-- Color Selection -->
                        <div class="col-md-4">
                            <x-form-group name="warna" label="Warna">
                                <x-form-select class="form-select form-select-solid" modifier="live" name="warna"
                                    :options="$this->colors" />
                            </x-form-group>
                        </div>

                        <!-- Storage Selection -->
                        <div class="col-md-4">
                            <x-form-group name="penyimpanan" label="Penyimpanan">
                                <x-form-select class="form-select form-select-solid" modifier="live" name="penyimpanan"
                                    :options="$this->storages" />
                            </x-form-group>
                        </div>
                        <div class="col-md-4">
                            <x-form-group name="imei" label="IMEI">
                                <x-form-input type="text" name="imei" wire:model="imei" placeholder="Nomor IMEI" />
                            </x-form-group>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-4">
                            <x-form-group name="icloud" label="iCloud">
                                <x-form-input type="text" name="icloud" wire:model="icloud" placeholder="Akun iCloud" />
                            </x-form-group>
                        </div>
                        <!-- Rent Price -->
                        <div class="col-md-4">
                            <x-form-group name="rent_price" label="Harga Sewa (per hari)">
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <x-form-input type="number" name="harga_sewa" placeholder="Harga sewa" />
                                </div>
                            </x-form-group>
                        </div>

                        <!-- Sale Price -->
                        <div class="col-md-4">
                            <x-form-group name="sale_price" label="Harga Jual (opsional)">
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <x-form-input type="number" name="harga_jual" placeholder="Harga jual" />
                                </div>
                            </x-form-group>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-3 mt-8">
                        <a wire:navigate href="{{ route('product-branch') }}" class="btn btn-light rounded-pill px-6">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-6" wire:loading.attr="disabled">
                            <span wire:loading.remove>Simpan</span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>