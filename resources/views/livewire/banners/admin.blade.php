<?php
use App\Models\Banner;
use function Livewire\Volt\{computed, mount, state, usesPagination};

usesPagination(theme: 'bootstrap');
state(['search', 'status' => 'a'])->url();
state(['sortColumn' => '', 'sortDirection' => 'ASC']);

$sort = function ($columnName) {
    if ($this->sortColumn === $columnName) {
        $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
    } else {
        $this->sortColumn = $columnName;
        $this->sortDirection = 'ASC';
    }
};

$totalBanner = computed(function () {
    return Banner::query()
        ->when($this->search, function ($query) {
            $query->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('category', 'like', '%' . $this->search . '%');
        })
        ->when($this->status, function ($query) {
            $query->where('st', $this->status);
        })
        ->count();
});

$banner = computed(function () {
    return Banner::query()
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('category', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->status, function ($query) {
            $query->where('st', $this->status);
        })
        ->when($this->sortColumn, function ($query) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }, function ($query) {
            $query->orderBy('id', 'DESC');
        })
        ->paginate(10);
});
$hapus = function ($data) {
    $data = Banner::find($data);
    $data->delete();
    return $this->redirect(route('Banner'), navigate: true);
};
?>
<div>
     <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
       <div class="card card-flush mb-7">
                <div class="card-body pt-5">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 gap-md-6 py-3">
                        <!-- Title Section -->
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <h2 class="h3 fw-bold mb-0">
                                        <span class="text-primary">{{ $this->totalBanner }}</span> Banner Ditemukan
                                    </h2>
                                </div>
                            </div>
                            @if($this->search)
                                <span class="text-muted fs-6 mt-2">
                                    untuk pencarian "<span class="fw-semibold">{{ $this->search }}</span>"
                                </span>
                            @else
                                <span class="text-muted fs-6 mt-2">
                                    Kelola Banner Anda dengan mudah
                                </span>
                            @endif
                        </div>
                        <!-- Controls Section -->
                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-end">
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <input type="search" wire:model.live="search" class="form-control ps-5"
                                    placeholder="Cari Merek..." aria-label="Search brand">
                            </div>
                            <!-- Status Filter -->
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <select class="form-select" wire:model.live="status" aria-label="Filter by status">
                                    <option value="">Semua Status</option>
                                    <option value="a">Aktif</option>
                                    <option value="i">Nonaktif</option>
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
                            <a href="{{ route('banner.create') }}" wire:navigate
                                class="btn btn-light-primary w-50 d-flex align-items-center justify-content-center px-3 rounded-1 hover-elevate-up"
                                aria-label="Add new category">
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
                            <span class="fs-2hx fw-bold text-primary me-2">{{ Banner::count() }}</span>
                        </div>
                        <span class="text-gray-600">Total Banner</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-success card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span
                                class="fs-2hx fw-bold text-success me-2">{{ Banner::where('st', 'a')->count() }}</span>
                        </div>
                        <span class="text-gray-600">Active Banner</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-danger card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-danger me-2">{{ Banner::where('st', 'i')->count() }}</span>
                        </div>
                        <span class="text-gray-600">Inactive Banner</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-light-info card-flush h-md-100">
                <div class="card-body">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <span class="fs-2hx fw-bold text-info me-2">{{ $this->totalBanner }}</span>
                        </div>
                        <span class="text-gray-600">Filtered Results</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 gy-7" id="Banner_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-150px">img-thumbnail</th>
                            <th class="min-w-100px text-center cursor-pointer" wire:click="sort('title')">
                                <span>Title</span>
                                @if($sortColumn === 'title')
                                    <i
                                        class="ki-outline ki-arrow-{{ $sortDirection === 'ASC' ? 'up' : 'down' }} fs-3 text-primary"></i>
                                @endif
                            </th>
                            <th class="min-w-100px text-center">kategori</th>
                            <th class="min-w-100px text-center">Status</th>
                            <th class="min-w-100px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @forelse ($this->banner as $banner)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="img-thumbnail me-3">
                                            <img src="{{ asset('storage/' . $banner->thumbnail) }}"
                                                alt="{{ $banner->name }}" width="270" height="150">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-gray-800 fw-bold d-block">{{ $banner->title }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-gray-800 fw-bold d-block">{{ $banner->category }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-{{ $banner->st === 'a' ? 'success' : 'danger' }}">
                                        {{ $banner->st === 'a' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a wire:navigate href="{{ route('banner.edit', ['banner' => $banner]) }}"
                                            class="btn btn-icon btn-sm btn-light-warning" data-bs-toggle="tooltip"
                                            title="Edit">
                                            <i class="ki-outline ki-notepad-edit fs-2"></i>
                                        </a>
                                        <button onclick="hapus({{ $banner->id }});" type="button"
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
                                        <span class="text-gray-600 fs-6">No Banner found matching your criteria</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($this->banner->hasPages())
                <div class="card-footer">
                    {{ $this->banner->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" tabindex="-1" id="exportModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Export Banner</h3>
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
     </div>
</div>