<?php
use App\Models\Master\Category;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function Livewire\Volt\{mount, rules, state, usesFileUploads};

usesFileUploads();

state(['category']);
state([
    'name' => '',
    'thumbnail' => '',
    'st' => ''
]);

rules(fn() => [
    'name' => 'required|unique:categories,name',
    'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
    'st' => 'required'
]);

mount(function () {
    $this->name = $this->category->name ?? '';
    $this->st = $this->category->st ?? '';
    $this->thumbnail = $this->category->thumbnail ?? '';
});

$save = function () {
    $this->validate();

    $dbPath = $this->category->thumbnail ?? null;

    if ($this->thumbnail && is_object($this->thumbnail)) {
        $slug = Str::slug($this->name);

        $customPath = $this->thumbnail->storeAs(
            'category',
            $slug . '.' . $this->thumbnail->extension()
        );

        $dbPath = 'category/' . $slug . '.' . $this->thumbnail->extension();

        // hapus file temporary setelah disimpan
        if (File::exists($this->thumbnail->getRealPath())) {
            File::delete($this->thumbnail->getRealPath());
        }
    }

    if ($this->category) {
        if ($this->category->thumbnail && $dbPath !== $this->category->thumbnail) {
            Storage::delete('storage/' . $this->category->thumbnail);
        }

        $this->category->castAndUpdate([
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'thumbnail' => $dbPath,
            'st' => $this->st,
        ]);
    } else {
        Category::castAndCreate([
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'thumbnail' => $dbPath,
            'st' => $this->st,
        ]);
    }

    $this->cleanupLivewireTempFiles();

    $this->dispatch('toast-success', message: "Data berhasil disimpan");

    return $this->redirect(route('category'), navigate: true);
};

$cleanupLivewireTempFiles = function () {
    $tempDirectory = storage_path('app/livewire-tmp');

    if (File::isDirectory($tempDirectory)) {
        File::cleanDirectory($tempDirectory);
    }
};
?>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <x-form action="save" hasFiles class="form d-flex flex-column flex-lg-row">
            <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>Thumbnail</h2>
                        </div>
                    </div>
                    <div class="card-body text-center pt-0">
                        <div class="image-input {{ $this->category == null ? 'image-input-empty image-input-placeholder' : 'image-input-circle' }}"
                            data-kt-image-input="true">
                            <div class="image-input-wrapper w-125px h-125px"
                                style="background-image: url({{ $this->category != null ? asset('storage/' . $this->category->thumbnail) : '' }})">
                            </div>
                            <label
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="change" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                title="Pilih Foto">
                                <i class="ki-filled ki-pencil fs-6"></i>
                                <input type="file" wire:model="thumbnail" accept=".png,.jpg,.jpeg" />
                                <input type="hidden" name="avatar_remove" />
                            </label>
                            <span
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="cancel" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                title="Batalkan">
                                <i class="ki-filled ki-cross fs-3"></i>
                            </span>
                            <span
                                class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="remove" data-bs-toggle="tooltip" data-bs-dismiss="click"
                                wire:click="removeAvatar" title="Hapus">
                                <i class="ki-filled ki-trash fs-3"></i>
                            </span>
                        </div>
                        <div class="text-muted fs-7">Atur gambar mini kategori. Hanya file gambar *.png, *.jpg, dan
                            *.jpeg
                            yang diterima.</div>
                    </div>
                </div>
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>Status</h2>
                        </div>
                        <div class="card-toolbar">
                            <div class="rounded-circle bg-success w-15px h-15px" id="kt_ecommerce_add_category_status">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @php
                            $data = [
                                '' => 'Pilih Status kategori',
                                'a' => 'Aktif',
                                'i' => 'Tidak Aktif'
                            ];
                        @endphp
                        <x-form-select name="st" class="form-select form-select-solid fw-bold" :options="$data" />
                        <div class="text-muted fs-7">Tetapkan status kategori</div>
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
                            <x-form-input-group>
                                <x-form-group name="name" label="Nama Kategori" required>
                                    <x-form-input type="text" name="name" class="bg-transparent"
                                        placeholder="Nama Kategori">
                                        @slot('help')
                                        <small class="form-text text-muted">
                                            Nama kategori wajib diisi dan sebaiknya unik.
                                        </small>
                                        @endslot
                                    </x-form-input>
                                </x-form-group>
                            </x-form-input-group>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('category') }}" wire:navigate class="btn btn-light me-5">Batalkan</a>
                    <x-button class="btn btn-primary" submit="true" indicator="Harap tunggu..." label="Simpan" />
                </div>
            </div>
        </x-form>
    </div>
</div>