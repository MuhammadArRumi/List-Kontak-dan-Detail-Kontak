<?php
use function Livewire\Volt\{mount, state, rules, usesFileUploads};
usesFileUploads();
state(['banner']);
state(
    [
        'title',
        'category',
        'thumbnail',
        'st'
    ]
);

rules(fn() => [
    'title' => 'required|string|max:255',
    'category' => 'required|string|max:255',
    'st' => 'required|in:i,a',
    'thumbnail' => 'required|image|mimes:png,jpg,jpeg|max:1024',
]);

mount(function () {
    if ($this->banner) {
        $this->title = $this->banner->title;
        $this->category = $this->banner->category;
        $this->st = $this->banner->st;
        $this->thumbnail = $this->banner->thumbnail;
    }
});
$save = function () {
    $this->validate();
    $thumbnailPath = $this->thumbnail->store('banner', 'public');

    $data = [
        'title' => $this->title,
        'category' => $this->category,
        'st' => $this->st,
        'thumbnail' => $thumbnailPath,
    ];

    if ($this->banner) {
        $this->banner->castAndUpdate($data);
        session()->flash('message', 'Banner berhasil diperbarui!');
    } else {
        \App\Models\Banner::castAndCreate($data);
        session()->flash('message', 'Banner berhasil dibuat!');
    }

    return $this->redirect(route('banner'), navigate: true);
};


?>

<div>
    <!-- Flash Message -->
    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-xxl">
            <!--begin::Layout-->
            <div class="d-flex flex-column flex-lg-row">
                <!--begin::Content-->
                <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
                    <!--begin::Card-->
                    <div class="card">
                        <!--begin::Card body-->
                        <div class="card-body p-12">
                            <!--begin::Form-->
                            <form wire:submit="save" id="kt_invoice_form">
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column align-items-start flex-xxl-row">
                                    <!--begin::Input group-->
                                    <div
                                        class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4">
                                        <span class="fs-2x fw-bold text-gray-800">
                                            @if ($this->banner)
                                                Edit Banner
                                            @else
                                                Tambah Banner Baru
                                            @endif
                                        </span>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Top-->
                                <!--begin::Separator-->
                                <div class="separator separator-dashed my-10"></div>
                                <!--end::Separator-->
                                <!--begin::Wrapper-->
                                <div class="mb-0">
                                    <!--begin::Row-->
                                    <div class="row gx-10 mb-5">
                                        <!--begin::Col-->
                                        <div class="col-lg-6">
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">Judul<span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control form-control-solid @error('title') is-invalid @enderror"
                                                    placeholder="Judul" wire:model="title" name="title" />
                                                @error('title') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <label class="form-label fs-6 text-gray-500 mb-3 mt-1">Kode Promo Wajib
                                                    diisi dan harus Unik.</label>
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">Kategori<span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control form-control-solid @error('category') is-invalid @enderror"
                                                    placeholder="Kategori" wire:model="category" name="category" />
                                                @error('category') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <label class="form-label fs-6 text-gray-500 mb-3 mt-1">Kode Promo Wajib
                                                    diisi dan harus Unik.</label>
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">Thumbnail
                                                    Promo</label>
                                                <div class="text-center pt-0">
                                                    <!--begin::Image input-->
                                                    <div class="image-input image-input-outline mb-3"
                                                        data-kt-image-input="true">
                                                        <!--begin::Preview existing avatar-->
                                                        @php
                                                            $thumbnailUrl = null;

                                                            if ($this->thumbnail instanceof \Livewire\TemporaryUploadedFile) {
                                                                $thumbnailUrl = $this->thumbnail->temporaryUrl();
                                                            } elseif ($this->banner && $this->banner->thumbnail) {
                                                                $thumbnailUrl = asset('storage/' . $this->banner->thumbnail);
                                                            } else {
                                                                $thumbnailUrl = asset('assets/media/svg/files/blank-image.svg');
                                                            }
                                                        @endphp

                                                        <div class="image-input-wrapper w-550px h-200px"
                                                            style="background-image: url('{{ $thumbnailUrl }}')">
                                                        </div>

                                                        <!--end::Preview existing avatar-->
                                                        <!--begin::Label-->
                                                        <label
                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                                            title="Ganti Thumbnail">
                                                            <i class="ki-duotone ki-pencil fs-7">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                            <!--begin::Inputs-->
                                                            <input type="file" wire:model="thumbnail"
                                                                accept=".png, .jpg, .jpeg" />
                                                            <!--end::Inputs-->
                                                        </label>
                                                        <!--end::Label-->
                                                        <!--begin::Cancel-->
                                                        <span
                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                                            title="Cancel avatar">
                                                            <i class="ki-duotone ki-cross fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                        <!--end::Cancel-->
                                                        <!--begin::Remove-->
                                                        <span
                                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                                            title="Remove avatar" wire:click="$set('thumbnail', null)">
                                                            <i class="ki-duotone ki-cross fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </span>
                                                        <!--end::Remove-->
                                                    </div>
                                                    <!--end::Image input-->
                                                    @error('thumbnail') <div class="text-danger small">{{ $message }}
                                                    </div> @enderror
                                                    <!--begin::Description-->
                                                    <div class="text-muted fs-7">Atur Thumbnail Promo. <br> Hanya format
                                                        <span class="text-danger">*.png, *.jpg dan *.jpeg</span><br>
                                                        yang diterima
                                                    </div>
                                                    <!--end::Description-->
                                                </div>
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Col-->
                                        <div class="col-lg-6">
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <!--begin::Label-->
                                                <label class="form-label fw-bold fs-6 text-gray-700">Status</label>
                                                <!--end::Label-->
                                                <!--begin::Select-->
                                                <select class="form-select @error('st') is-invalid @enderror"
                                                    wire:model="st" name="st">
                                                    <option value="" selected>-- Pilih Status --</option>
                                                    <option value="a">Aktif</option>
                                                    <option value="i">Nonaktif</option>
                                                </select>
                                                @error('st') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <!--end::Select-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Separator-->
                                            <div class="separator separator-dashed mb-8"></div>
                                            <!--end::Separator-->
                                            <!--begin::Actions-->
                                            <div class="mb-0">
                                                <button type="submit" class="btn btn-primary w-100"
                                                    id="kt_invoice_submit_button" wire:loading.attr="disabled">
                                                    <span wire:loading.remove>
                                                        @if ($this->Banner)
                                                            Simpan Banner
                                                        @else
                                                            Tambah Banner
                                                        @endif
                                                    </span>
                                                    <span wire:loading>Menyimpan...</span>
                                                </button>
                                            </div>
                                            <!--end::Actions-->
                                        </div>
                                    </div>
                                    <!--end::Col-->
                                    <!--end::Row-->
                                    <!--end::Wrapper-->
                                </div>
                                <!--end::Card body-->
                        </div>
                        <!--end::Card-->
                    </div>
                    <!--end::Content-->
                    <!--end::Content container-->
                </div>
            </div>