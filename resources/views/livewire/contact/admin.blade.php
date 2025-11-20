<?php

use App\Models\Contact;
use function Livewire\Volt\{state, with, usesPagination};
use Illuminate\Support\Str;

// Mengaktifkan fitur pagination Livewire
usesPagination();

// Mendefinisikan state untuk pencarian
state(['search' => '']);

// Mengambil data kontak dengan kondisi pencarian dan pagination
with(fn () => [
    'contacts' => Contact::when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('message', 'like', '%'.$this->search.'%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
]);

// Fungsi untuk menghapus input pencarian
$clearSearch = function () {
    $this->search = '';
};

?>

<div class="card card-flush card-xl-stretch mb-5 mb-xl-8 shadow-sm">
    <div class="card-header border-0 pt-6 pb-0">
        <div class="card-title">
            <h3 class="card-label">Daftar Kontak</h3>
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
            <div class="w-100 mw-300px">
                {{-- Border pada input search akan sangat diperjelas di sini --}}
                <div class="input-group input-group-solid custom-search-input rounded-3 bg-light-secondary hover-border-primary transition-all duration-300 ease-in-out focus-within:border-primary focus-within:ring-2 focus-within:ring-primary focus-within:ring-opacity-25">
                    <span class="input-group-text bg-transparent border-0 pe-2">
                        {{-- Ikon Pencarian - Menggunakan SVG umum sebagai contoh --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search text-gray-500 transition-all duration-300 ease-in-out group-focus-within:text-primary">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="form-control bg-transparent border-0 ps-1 text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-0"
                        placeholder="Cari nama, email, atau pesan..."
                        aria-label="Cari kontak"
                    />
                    
                    {{-- Tombol Hapus --}}
                    <div
                        x-data="{ showClear: @entangle('search').live }"
                        x-show="showClear.length > 0"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-75"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-75"
                        class="input-group-text bg-transparent border-0 ps-2"
                    >
                        <i
                            wire:click="clearSearch"
                            class="ki-duotone ki-cross-circle fs-3 text-gray-500 cursor-pointer text-hover-danger"
                            title="Hapus Pencarian"
                        >
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3" id="kt_contacts_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-200px ps-9">Nama Pengirim</th>
                        <th>Email</th>
                        <th>Pesan</th>
                        <th class="text-end min-w-100px pe-9">Waktu</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse($contacts as $contact)
                    <tr
                        wire:key="contact-{{ $contact->id }}"
                        onclick="window.location.href='{{ route('contact.show', $contact->id) }}'"
                        class="contact-row-interactive"
                        x-data="{ isHovering: false }"
                        x-on:mouseenter="isHovering = true"
                        x-on:mouseleave="isHovering = false"
                    >
                        <td class="ps-9">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-35px symbol-circle me-4">
                                    <div
                                        class="symbol-label bg-primary text-white fs-4 fw-bold"
                                        :class="{ 'bg-secondary text-gray-800-hover': isHovering }"
                                    >
                                        {{ substr($contact->name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-start flex-column">
                                    <a
                                        href="{{ route('contact.show', $contact->id) }}"
                                        class="text-gray-900 fw-bold text-hover-primary fs-6"
                                        :class="{ 'text-primary-hover': isHovering }"
                                    >{{ $contact->name }}</a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-gray-600 fs-7" :class="{ 'text-gray-800-hover': isHovering }">{{ $contact->email }}</div>
                        </td>
                        <td>
                            <div class="text-muted fs-7" title="{{ $contact->message }}" :class="{ 'text-gray-800-hover': isHovering }">{{ Str::limit($contact->message, 30) }}</div>
                        </td>
                        <td class="text-end pe-9">
                            <div class="d-flex flex-column align-items-end">
                                <div class="text-gray-900 fw-bold fs-6" :class="{ 'text-primary-hover': isHovering }">{{ $contact->created_at->format('H:i') }}</div>
                                <div class="text-muted fs-7" :class="{ 'text-gray-800-hover': isHovering }">{{ $contact->created_at->format('d M M Y') }}</div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="d-flex flex-column flex-center w-100 min-h-350px">
                                <i class="ki-duotone ki-file-manager fs-3x text-gray-500 mb-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                                <div class="fs-5 fw-bold text-gray-800 mb-2">Tidak ada hasil ditemukan</div>
                                <div class="fs-7 text-gray-600">
                                    @if($search)
                                        <p>Kami tidak dapat menemukan kontak yang cocok dengan pencarian Anda: "<span class="fw-bolder text-gray-800">{{ $search }}</span>".</p>
                                        <p class="center">Coba sesuaikan kata kunci Anda atau <span wire:click="clearSearch" class="text-primary cursor-pointer fw-bold">lihat semua kontak</span>.</p>
                                    @else
                                        <p>Belum ada data kontak yang tersedia saat ini.</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
        <div class="d-flex flex-stack flex-wrap pt-8">
            <div class="fs-7 fw-semibold text-gray-600">
                Menampilkan <span class="fw-bolder">{{ $contacts->firstItem() }}</span> hingga <span class="fw-bolder">{{ $contacts->lastItem() }}</span> dari <span class="fw-bolder">{{ $contacts->total() }}</span> data
            </div>

            <div class="d-flex flex-wrap">
                <ul class="pagination pagination-sm m-0">
                    {{-- Previous Page Link --}}
                    @if ($contacts->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link" aria-hidden="true">&laquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled" rel="prev">&laquo;</button>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @php
                        $currentPage = $contacts->currentPage();
                        $lastPage = $contacts->lastPage();
                        $start = max(1, $currentPage - 2);
                        $end = min($lastPage, $currentPage + 2);

                        if ($currentPage < 3) {
                            $end = min($lastPage, 5);
                        }
                        if ($currentPage > $lastPage - 2) {
                            $start = max(1, $lastPage - 4);
                        }
                    @endphp

                    @if ($start > 1)
                        <li class="page-item"><button type="button" class="page-link" wire:click="gotoPage(1)">1</button></li>
                        @if ($start > 2)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                        @endif
                    @endif

                    @foreach (range($start, $end) as $page)
                        @if ($page == $currentPage)
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button></li>
                        @endif
                    @endforeach

                    @if ($end < $lastPage)
                        @if ($end < $lastPage - 1)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                        @endif
                        <li class="page-item"><button type="button" class="page-link" wire:click="gotoPage({{ $lastPage }})">{{ $lastPage }}</button></li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($contacts->hasMorePages())
                        <li class="page-item">
                            <button type="button" class="page-link" wire:click="nextPage" wire:loading.attr="disabled" rel="next">&raquo;</button>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link" aria-hidden="true">&raquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
    </div>
    
    <style>
        /* Mengubah kursor menjadi pointer saat di-hover */
        .contact-row-interactive {
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Efek hover yang sedikit lebih gelap dari default */
        .table tbody tr.contact-row-interactive:hover {
            background-color: rgba(0, 0, 0, 0.07) !important; /* Abu-abu agak gelap transparan */
        }

        /* Kelas untuk mengubah warna teks menjadi abu-abu gelap saat hover */
        .text-gray-800-hover {
            color: #333 !important; /* Abu-abu gelap */
        }

        /* Kelas untuk mengubah warna teks menjadi warna primary saat hover */
        .text-primary-hover {
            color: var(--kt-primary) !important; /* Menggunakan variabel warna primary dari Metronic */
        }

        /* Pastikan elemen-elemen di dalam baris juga memiliki transisi */
        .symbol-label,
        .text-gray-900,
        .text-gray-600,
        .text-muted {
            transition: color 0.3s ease, background-color 0.3s ease;
        }

        /* Menjaga garis putus-putus antar baris */
        .table.table-row-dashed tbody tr {
            border-bottom: 1px dashed var(--kt-border-color);
        }

        /* Menggunakan warna secondary dari Metronic untuk background symbol saat hover */
        .bg-secondary {
            background-color: var(--kt-secondary) !important; /* Warna secondary dari tema Metronic */
        }

        /* ==== STYLE UNTUK SEARCH INPUT YANG LEBIH PROFESIONAL ==== */

        /* Saya menambahkan class baru 'custom-search-input' di HTML untuk targeting yang lebih spesifik */
        .custom-search-input {
            background-color: var(--kt-light-secondary) !important;
            /* Ubah ketebalan border menjadi 1.5px */
            border: 0.7mm solid #D8D8D8 !important; /* Border abu-abu terang yang jelas */
            border-radius: 0.625rem !important;
            box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1) !important;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out !important;
        }

        .custom-search-input:hover {
            border-color: #007bff !important; /* Contoh warna biru terang untuk hover */
            box-shadow: 0px 2px 5px rgba(0, 123, 255, 0.3) !important; /* Shadow yang lebih menonjol saat hover */
        }

        .custom-search-input:focus-within {
            border-color: #0056b3 !important; /* Contoh warna biru yang lebih gelap untuk fokus */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.4) !important; /* Shadow fokus yang sangat jelas */
        }

        /* Mengatur elemen di dalam input group */
        .custom-search-input .form-control,
        .custom-search-input .input-group-text {
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Mengatur warna teks placeholder */
        .form-control::placeholder {
            color: #999 !important; /* Placeholder yang lebih gelap */
            opacity: 1 !important;
        }

        /* Mengatur ikon pencarian */
        .input-group-text svg {
            transition: color 0.3s ease-in-out !important;
            color: #888 !important; /* Warna ikon default */
        }
        .custom-search-input:focus-within .input-group-text svg {
            color: #007bff !important; /* Warna ikon saat fokus */
        }

        /* Mengatur tombol clear */
        .input-group-text .ki-duotone.ki-cross-circle {
            transition: color 0.3s ease-in-out !important;
        }
        .input-group-text .ki-duotone.ki-cross-circle:hover {
            color: #dc3545 !important; /* Warna merah untuk hover */
        }
    </style>
</div>