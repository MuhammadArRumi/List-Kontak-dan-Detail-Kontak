<?php
use App\Models\Faq;
use function Livewire\Volt\{computed, mount, state, usesPagination};

usesPagination(theme: 'bootstrap');
state(['search', 'status' => 1])->url();
state(['sortColumn' => '', 'sortDirection' => 'ASC']);

$sort = function ($columnName) {
    if ($this->sortColumn === $columnName) {
        $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
    } else {
        $this->sortColumn = $columnName;
        $this->sortDirection = 'ASC';
    }
};

$totalFaq = computed(function () {
    return Faq::query()
        ->when($this->search, function ($query) {
            $query->where('question', 'like', '%' . $this->search . '%')
                ->orWhere('answer', 'like', '%' . $this->search . '%');
        })
        ->when($this->status, function ($query) {
            $query->where('active', $this->status);
        })
        ->count();
});

$faq = computed(function () {
    return Faq::query()
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('question', 'like', '%' . $this->search . '%')
                    ->orWhere('answer', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->status, function ($query) {
            $query->where('active', $this->status);
        })
        ->when($this->sortColumn, function ($query) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }, function ($query) {
            $query->orderBy('id', 'DESC');
        })
        ->paginate(10);
});
$hapus = function ($data) {
    $data = Faq::find($data);
    $data->delete();
    return $this->redirect(route('faq'), navigate: true);
};
?>
<div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 gap-md-6 py-3">
        <!-- Title Section -->
        <div class="d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h2 class="h3 fw-bold mb-0">
                        <span class="text-primary">{{ $this->totalFaq }}</span> Kategori Ditemukan
                        @if($this->search)
                            <span class="text-muted fs-6 ms-2">
                                untuk pencarian "<span class="fw-semibold">{{ $this->search }}</span>"
                            </span>
                        @endif
                    </h2>
                </div>

                <!-- Floating Button -->
                <a href="{{ route('faq.create') }}" wire:navigate
                    class="btn btn-light-primary d-flex d-xl-none align-items-center gap-2 rounded-1 hover-elevate-up"
                    aria-label="Add new faq">
                    <i class="ki-outline ki-plus fs-5"></i>
                    <span class="d-none d-md-inline">Tambah</span>
                </a>
            </div>
            <p class="text-muted mb-0">Kelola kategori produk Anda dengan mudah</p>
        </div>

        <!-- Controls Section -->
        <div class="d-flex flex-column flex-sm-row gap-3 align-items-end">
            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                <input type="search" wire:model.live="search" class="form-control ps-5" placeholder="Cari kategori..."
                    aria-label="Search categories">
            </div>
            <!-- Status Filter -->
            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                <select class="form-select" wire:model.live="status" aria-label="Filter by status">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value=" ">Nonaktif</option>
                </select>
            </div>

            <!-- Sort By -->
            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                <select class="form-select" wire:model.live="sortColumn" aria-label="Sort by">
                    <option value="">Urutkan</option>
                    <option value="name">Nama (A-Z)</option>
                    <option value="created_at">Terbaru</option>
                </select>
            </div>

            <!-- Add Button - Now with consistent width -->
            <a href="{{ route('faq.create') }}" wire:navigate
                class="btn btn-light-primary w-100 d-flex align-items-center justify-content-center px-3 rounded-1 hover-elevate-up"
                aria-label="Add new faq">
                <i class="ki-outline ki-plus fs-5 me-2"></i>
                <span class="text-nowrap">Tambah</span>
            </a>
        </div>
    </div>
    <div class="row g-5 mb-5">
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-primary card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-primary me-2">{{ Faq::count() }}</span>
                        </div>
                        <span class="text-gray-600">Total faq</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-success card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-success me-2">{{ faq::where('active', 1)->count() }}</span>
                        </div>
                        <span class="text-gray-600">Active faq</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-danger card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-danger me-2">{{ faq::where('active', 0)->count() }}</span>
                        </div>
                        <span class="text-gray-600">Inactive faq</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-info card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-info me-2">{{ $this->totalFaq }}</span>
                        </div>
                        <span class="text-gray-600">Filtered Results</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- faq Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 gy-7" id="faq_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-150px cursor-pointer" wire:click="sort('name')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Pertanyaan</span>
                                    @if($sortColumn === 'question')
                                        <i
                                            class="ki-outline ki-arrow-{{ $sortDirection === 'ASC' ? 'up' : 'down' }} fs-3 text-primary"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="min-w-100px text-center">Jawaban</th>
                            <th class="min-w-100px text-center">Kategori</th>
                            <th class="min-w-100px text-center">status</th>
                            <th class="min-w-100px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @forelse ($this->faq as $faq)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <span class="text-gray-800 fw-bold d-block">{{ $faq->question }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-gray-800 fw-bold d-block">{{ $faq->answer }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-gray-800 fw-bold d-block">{{ $faq->category->name ?? 'N/A'}}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-{{ $faq->active == 1 ? 'success' : 'danger' }}">
                                        {{ $faq->active == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a wire:navigate href="{{ route('faq.edit', ['faq' => $faq]) }}"
                                            class="btn btn-icon btn-sm btn-light-warning" data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="ki-outline ki-notepad-edit fs-2"></i>
                                        </a>
                                        <a wire:navigate href="{{ route('faq.edit', ['faq' => $faq]) }}"
                                            class="btn btn-icon btn-sm btn-light-success" data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="ki-outline ki-eye fs-2"></i>
                                        </a>
                                        <button onclick="hapus({{ $faq->id }});" type="button"
                                            class="btn btn-icon btn-sm btn-light-danger" data-bs-toggle="tooltip"
                                            title="Delete">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ki-outline ki-search-list fs-4x text-muted mb-4"></i>
                                        <span class="text-gray-600 fs-6">No faq found matching your criteria</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($this->faq->hasPages())
                <div class="card-footer">
                    {{ $this->faq->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" tabindex="-1" id="exportModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Export faq</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="mb-5">
                        <label class="form-label">Select Format</label>
                        <select class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Select Columns</label>
                        <select class="form-select form-select-solid" multiple="multiple" data-control="select2"
                            data-close-on-select="false">
                            <option value="name" selected>Name</option>
                            <option value="slug" selected>Slug</option>
                            <option value="status" selected>Status</option>
                            <option value="created_at" selected>Created At</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Export</button>
                </div>
            </div>
        </div>
    </div>
    @section('custom_js')
        <script data-navigate-once>
            function hapus(data) {
                Swal.fire({
                    title: 'Hapus Merek ?',
                    text: 'Apakah Anda yakin ingin menghapus data ini ? Tindakan ini tidak dapat dibatalkan',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batalkan',
                    reverseButtons: true,
                    backdrop: true,
                    allowOutsideClick: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menghapus Data...',
                            html: 'Sedang memproses permintaan Anda',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                @this.hapus(data).then(() => {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: 'Data berhasil di hapus',
                                        icon: 'success',
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                }).catch(error => {
                                    Swal.fire({
                                        title: 'Gagal!',
                                        html: `Gagal menghapus data: <br><span class="text-red-500">${error.message}</span>`,
                                        icon: 'error'
                                    });
                                });
                            }
                        });
                    }
                });
            }
        </script>
    @endsection
</div>