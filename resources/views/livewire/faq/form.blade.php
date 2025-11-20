<?php
use function Livewire\Volt\{mount, state, rules, with};
state(['faq']);
state(
    [
        'question',
        'faq_category_id',
        'answer',
        'active'
    ]
);

rules(fn() => [
    'question' => 'required|string|max:255',
    'answer' => 'required|string|max:255',
    'active' => 'required|in:0,1',
    'faq_category_id' => 'required',
]);

mount(function () {
    if ($this->faq) {
        $this->question = $this->faq->question;
        $this->answer = $this->faq->answer;
        $this->faq_category_id = $this->faq->faq_category_id;
        $this->active = $this->faq->active;
    }
});

with([
    'faqCategory' => \App\Models\Master\FaqCategories::all(),
]);

$save = function () {
    $this->validate();
    $data = [
        'question' => $this->question,
        'answer' => $this->answer,
        'faq_category_id' => $this->faq_category_id,
        'active' => $this->active,
    ];

    if ($this->faq) {
        $this->faq->update($data);
        session()->flash('message', 'faq berhasil diperbarui!');
    } else {
        \App\Models\Faq::create($data);
        session()->flash('message', 'faq berhasil dibuat!');
    }

    return $this->redirect(route('faq'), navigate: true);
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
                                            @if ($this->faq)
                                                Edit Faq
                                            @else
                                                Tambah Faq Baru
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
                                                <label class="form-label fs-5 fw-bold text-gray-700 mb-3">Kategori<span
                                                        class="text-danger">*</span></label>
                                                <select
                                                    class="form-select form-select-solid @error('faq_category_id') is-invalid @enderror"
                                                    wire:model="faq_category_id" name="faq_category_id">
                                                    <option value="">Pilih Tipe Kategori</option>
                                                    @foreach ($faqCategory as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('faq_category_id') <div class="invalid-feedback">{{ $message }}
                                                </div> @enderror

                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <label
                                                    class="form-label fs-5 fw-bold text-gray-700 mb-3">Pertanyaan<span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control form-control-solid @error('question') is-invalid @enderror"
                                                    placeholder="Pertanyaan" wire:model="question" name="question" />
                                                @error('question') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <label class="form-label fs-6 text-gray-500 mb-3 mt-1">Kode Promo Wajib
                                                    diisi dan harus Unik.</label>
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <label
                                                    class="form-label fs-5 fw-bold text-gray-700 mb-3">Jawaban</label>
                                                <textarea
                                                    class="form-control form-control-solid @error('answer') is-invalid @enderror"
                                                    rows="3" placeholder="Jawaban" wire:model="answer" name="answer"
                                                    name="answer"></textarea>
                                                @error('answer') <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-lg-6">
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <!--begin::Label-->
                                                <label class="form-label fw-bold fs-6 text-gray-700">Status</label>
                                                <!--end::Label-->
                                                <!--begin::Select-->
                                                <select class="form-select @error('active') is-invalid @enderror"
                                                    wire:model="active" name="active">
                                                    <option value="" selected>-- Pilih Status --</option>
                                                    <option value="1">Aktif</option>
                                                    <option value="0">Nonaktif</option>
                                                </select>
                                                @error('active') <div class="invalid-feedback">{{ $message }}</div>
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
                                                        @if ($this->Faq)
                                                            Simpan Faq
                                                        @else
                                                            Tambah Faq
                                                        @endif
                                                    </span>
                                                    <span wire:loading>Menyimpan...</span>
                                                </button>
                                            </div>
                                            <!--end::Actions-->
                                        </div>
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