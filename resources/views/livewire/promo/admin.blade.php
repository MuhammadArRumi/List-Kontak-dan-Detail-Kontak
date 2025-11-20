<?php
use App\Models\Promo;
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
$types = computed(function () {
    return [
        'percentage' => 'Persentase',
        'b1g1' => 'Buy 1 Get 1',
        'fixed_amount' => 'Potongan Tetap',
        'free_shipping' => 'Gratis Ongkir',
    ];
});

$scopes = computed(function () {
    return [
        'branches' => 'Cabang',
        'products' => 'Produk',
        'categories' => 'Kategori',
        'all' => 'Semua'
    ];
});

$applicables = computed(function () {
    return [
        'rent' => 'Sewa',
        'sale' => 'Jual',
        'all' => 'Semua'
    ];
});

$totalPromo = computed(function () {
    return Promo::query()
        ->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('slug', 'like', '%' . $this->search . '%');
        })
        ->when($this->status, function ($query) {
            $query->where('is_active', $this->status);
        })
        ->count();
});

$promos = computed(function () {
    return Promo::query()
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->status, function ($query) {
            $query->where('is_active', $this->status);
        })
        ->when($this->sortColumn, function ($query) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }, function ($query) {
            $query->orderBy('id', 'DESC');
        })
        ->paginate(10);
});
$hapus = function ($data) {
    $data = Promo::find($data);
    $data->delete();
    return $this->redirect(route('promo'), navigate: true);
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
                                        <span class="text-primary">{{ $this->totalPromo }}</span> Promo Ditemukan
                                    </h2>
                                </div>
                            </div>
                            @if($this->search)
                                <span class="text-muted fs-6 mt-2">
                                    untuk pencarian "<span class="fw-semibold">{{ $this->search }}</span>"
                                </span>
                            @else
                                <span class="text-muted fs-6 mt-2">
                                    Kelola Promo Anda dengan mudah
                                </span>
                            @endif
                        </div>
                        <!-- Controls Section -->
                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-end">
                            <div class="flex-grow-1 flex-sm-grow-0 w-100 w-sm-200px">
                                <input type="search" wire:model.live="search" class="form-control ps-5"
                                    placeholder="Cari Promo..." aria-label="Search brand">
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
                            <a href="{{ route('promo.create') }}" wire:navigate
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
                                    <span class="fs-2hx fw-bold text-primary me-2">{{ Promo::count() }}</span>
                                </div>
                                <span class="text-gray-600">Total Promo</span>
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
                                        class="fs-2hx fw-bold text-success me-2">{{ Promo::where('is_active', '1')->count() }}</span>
                                </div>
                                <span class="text-gray-600">Active Promo</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-danger card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span
                                        class="fs-2hx fw-bold text-danger me-2">{{ Promo::where('is_active', '0')->count() }}</span>
                                </div>
                                <span class="text-gray-600">Inactive Promo</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bg-light-info card-flush h-md-100">
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fs-2hx fw-bold text-info me-2">{{ $this->totalPromo }}</span>
                                </div>
                                <span class="text-gray-600">Filtered Results</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card py-5">
                <div class="card-body py-5">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 gy-7" id="table_promo">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px cursor-pointer" wire:click="sort('name')">
                                        Nama
                                        @if ($this->sortColumn === 'name')
                                            <i
                                                class="ki-outline {{ $this->sortDirection === 'ASC' ? 'ki-arrow-up' : 'ki-arrow-down' }} fs-3 text-primary"></i>
                                        @endif
                                    </th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Tipe</th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Nilai</th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Cakupan</th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Berlaku Untuk </th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Tanggal Awal</th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Tanggal Berakhir</th>
                                    <th class="text-center min-w-100px d-none d-md-table-cell">Status</th>
                                    <th class="text-end min-w-100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse ($this->promos as $item)
                                    <tr>
                                        <td class="d-flex align-items-center">
                                            <div class="img-thumbnail overflow-hidden me-3">
                                                <div class="symbol-label">
                                                    <img src="{{ $item->thumbnail ? asset('storage/' . $item->thumbnail) : asset('assets/media/illustrations/icons/tickets.png') }}"
                                                        alt="{{ $item->name }}" width="50" height="50">
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('user.show', ['user' => $item]) }}" wire:navigate
                                                    class="text-gray-800 text-hover-primary mb-1">
                                                    {{ $item->name }}
                                                </a>
                                                <div class="d-flex flex-column text-muted fs-7">
                                                    <span>{{ $item->code }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $this->types[$item->type] ?? 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $item->value ?? 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $this->scopes[$item->scope] ?? 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $this->applicables[$item->applicable_for] ?? 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $item->start_date ? $item->start_date->format('j F Y') : 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            {{ $item->end_date ? $item->end_date->format('j F Y') : 'N/A' }}
                                        </td>
                                        <td class="text-center d-none d-md-table-cell">
                                            @if ($item->is_active == 1)
                                                <span class="badge badge-light-success fs-7 fw-bold py-2 px-3">
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger fs-7 fw-bold py-2 px-3">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end align-items-center">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('promo.edit', ['promo' => $item]) }}" wire:navigate
                                                        class="btn btn-icon btn-sm btn-light-warning d-none d-md-inline-flex"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        aria-label="Lihat Data" title="Edit Promo">
                                                        <i class="ki-outline ki-notepad-edit fs-2"></i>
                                                    </a>
                                                    <button onclick="hapus({{ $item->id }});" type="button"
                                                        class="btn btn-icon btn-sm btn-light-danger"
                                                        data-bs-toggle="tooltip" title="Delete">
                                                        <i class="ki-outline ki-trash fs-2"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-10">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ki-outline ki-search-list fs-4x text-muted mb-4"></i>
                                                <span class="text-gray-600 fs-6">No Promo found matching your
                                                    criteria</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $this->promos->links() }}
                    </div>
                </div>
            </div>
            @section('custom_js')
                <script data-navigate-once>
                    function hapus(data) {
                        Swal.fire({
                            title: 'Hapus Promo ?',
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