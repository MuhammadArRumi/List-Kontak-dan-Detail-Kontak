<?php
use App\Models\Contact;
use function Livewire\Volt\{state, mount};

state(['contact']);

mount(function ($id) {
    $this->contact = Contact::findOrFail($id);
});
?>

<x-default-layout>
    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
        <!--begin::Content wrapper-->
        <div class="d-flex flex-column flex-column-fluid">
            <!--begin::Toolbar-->
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <!--begin::Toolbar container-->
                <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                    <!--begin::Page title-->
                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <!--begin::Title-->
                        <h1 class="page-heading d-flex text-gray-900 dark:text-white fw-bold fs-3 flex-column justify-content-center my-0">
                            Detail Pesan
                        </h1>
                        <!--end::Title-->
                        <!--begin::Breadcrumb-->
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <!--begin::Item-->
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="breadcrumb-item">
                                <span class="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="breadcrumb-item text-muted">
                                <a href="/contact" class="text-muted text-hover-primary">Kontak</a>
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="breadcrumb-item">
                                <span class="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <!--end::Item-->
                            <!--begin::Item-->
                            <li class="breadcrumb-item text-muted">Detail</li>
                            <!--end::Item-->
                        </ul>
                        <!--end::Breadcrumb-->
                    </div>
                    <!--end::Page title-->
                </div>
                <!--end::Toolbar container-->
            </div>
            <!--end::Toolbar-->
            <!--begin::Content-->
            <div id="kt_app_content" class="app-content flex-column-fluid">
                <!--begin::Content container-->
                <div id="kt_app_content_container" class="app-container container-xxl">
                    <!--begin::Card-->
                    <div class="card card-flush card-xl-stretch mb-5 mb-xl-10 shadow-sm overflow-hidden">
                        <!--begin::Card header dengan gradient yang lebih kuat-->
                        <div class="card-header border-0 pt-8 pb-5 position-relative" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(59, 130, 246, 0.15) 50%, rgba(96, 165, 250, 0.08) 100%);">
                            <!-- Efek overlay untuk memastikan gradient mencapai ujung -->
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(59, 130, 246, 0.15) 50%, rgba(96, 165, 250, 0.08) 100%);"></div>
                            <!--begin::Card title-->
                            <div class="card-title position-relative">
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-60px symbol-circle me-5">
                                        <div class="symbol-label bg-primary">
                                            <span class="text-white fs-2 fw-bold">{{ substr($contact->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::User info-->
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center">
                                            <span class="text-gray-900 dark:text-white fs-5 fw-semibold me-2">{{ $contact->name }}</span>
                                        </div>
                                        <div class="d-flex align-items-center mt-1">
                                            <span class="text-gray-700 dark:text-gray-300 fs-7">{{ $contact->email }}</span>
                                            <span class="bullet bg-gray-500 dark:bg-gray-400 w-2px h-2px mx-2"></span>
                                            <span class="text-gray-700 dark:text-gray-300 fs-7">{{ $contact->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    </div>
                                    <!--end::User info-->
                                </div>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <!--begin::Subject-->
                            <div class="mb-6 mt-6">
                                <h2 class="text-gray-900 dark:text-white fw-bold fs-4 mb-1">{{ $contact->subject }}</h2>
                            </div>
                            <!--end::Subject-->
                            
                            <!--begin::Message Content-->
                            <div class="mb-10">
                                <div class="separator separator-dashed my-8"></div>
                                <div class="bg-light-primary dark:bg-gray-800 rounded-lg p-8 border border-primary border-dashed dark:border-gray-600 shadow-sm position-relative overflow-hidden">
                                    <!--begin::Decorative element-->
                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary bg-opacity-5 dark:bg-gray-700 dark:bg-opacity-30"></div>
                                    <!--end::Decorative element-->
                                    <!--begin::Message content-->
                                    <div class="position-relative">
                                        <div class="text-gray-800 dark:text-gray-300 fs-6" style="line-height: 1.7;">
                                            {!! nl2br(e($contact->message)) !!}
                                        </div>
                                    </div>
                                    <!--end::Message content-->
                                </div>
                            </div>
                            <!--end::Message Content-->
                            
                            <!--begin::Actions-->
                            <div class="d-flex flex-wrap gap-3">
                                <a href="/contact" class="btn btn-flex btn-light-primary">
                                    <i class="ki-duotone ki-arrow-left fs-4 me-2"></i>
                                    Kembali ke List
                                </a>
                                @if(auth()->user()->can('delete contacts'))
                                <button type="button" class="btn btn-flex btn-danger" wire:click="delete({{ $contact->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pesan ini?">
                                    <i class="ki-duotone ki-trash fs-4 me-2"></i>
                                    Hapus Pesan
                                </button>
                                @endif
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Content container-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Content wrapper-->
    </div>
</x-default-layout>