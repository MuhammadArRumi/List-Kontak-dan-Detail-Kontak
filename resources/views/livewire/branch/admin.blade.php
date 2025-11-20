<?php
use App\Models\User;
use App\Models\Master\Branch;
use function Livewire\Volt\{computed, mount, state, usesPagination};

usesPagination(theme: 'bootstrap');

state(['search', 'layout', 'status'])->url();
state(['sortColumn' => '', 'sortDirection' => 'ASC']);

$sort = function ($columnName) {
    $this->sortColumn = $columnName;
    $this->sortDirection = $this->sortDirection == 'ASC' ? 'ASC' : 'DESC';
};
mount(function () {
    $this->layout = 'grid';
});
$collection = computed(function () {
    $query = Branch::select('branches.*')
        ->withCount('users')
        ->with('branchSchedules'); // [++]

    $role = Auth::user()->getRoleNames()[0];

    // Search filter
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%');
        });
    }

    // Role filter
    if ($role == "Super Admin" || $role == "Owner" || $role == "Konsumen") {
        // $query->where('', Auth::user()->profile->house_id);
    } elseif ($role == "Cabang" || $role == "Pegawai") {
        $query->where('id', Auth::user()->branch_id);
    }

    // Status filter
    // $query->where('st', $this->status);

    // Default sort
    if (!$this->sortColumn) {
        $this->sortColumn = 'id';
        $this->sortDirection = 'ASC';
    }

    return $query->orderBy('users_count', 'DESC')
        ->paginate(18);
});
$active = function (Branch $branch) {
    $branch->st = 'a';
    $branch->save();
    $this->dispatch('toast-success', message: "Cabang berhasil diaktifkan.");
};
$inactive = function (Branch $branch) {
    if ($branch->users_count > 0) {
        $this->dispatch('toast-info', message: "Cabang ini tidak dapat dinonaktifkan karena masih memiliki peminat.");
        return;
    }
    $branch->st = 'i';
    $branch->save();
    $this->dispatch('toast-success', message: "Cabang berhasil non aktifkan.");
}
?>
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">

        <x-advance-search />
        <div class="d-flex flex-wrap flex-stack mb-5">
            <!--begin::Title-->
            <div class="d-flex flex-wrap align-items-center my-1">
                <h3 class="fw-bold me-5 my-1">
                    {{ $this->collection->count() > 0 ? $this->collection->count() . ' Data Ditemukan' : '' }}
                    {{-- <span class="text-gray-500 fs-6">
                        Berdasarkan Pembaruan Terkini ↓
                    </span> --}}
                </h3>
            </div>
            <!--end::Title-->
            <!--begin::Controls-->
            <div class="d-flex flex-wrap my-1">
                <input type="radio" class="btn-check" name="layout" wire:model.live="layout" value="grid"
                    id="layout_grid" />
                <label class="btn btn-sm btn-icon btn-light btn-color-muted btn-active-primary me-3" for="layout_grid">
                    <i class="ki-outline ki-element-plus fs-2"></i>
                </label>
                <input type="radio" class="btn-check" name="layout" wire:model.live="layout" value="list"
                    id="layout_list" />
                <label class="btn btn-sm btn-icon btn-light btn-color-muted btn-active-primary me-3" for="layout_list">
                    <i class="ki-outline ki-row-horizontal fs-2"></i>
                </label>
                <!--begin::Actions-->
                <div class="d-flex my-0">
                    <!--begin::Select-->
                    <select name="status" data-control="select2" data-hide-search="true" data-placeholder="Filter"
                        class="form-select form-select-sm form-select-solid w-150px">
                        <option value="1">Urutkan berdasarkan A-Z</option>
                        <option value="2">Urutkan berdasarkan Z-A</option>
                    </select>
                    <!--end::Select-->
                    <!--begin::Select-->
                    <a href="{{ route('branch.create') }}"
                        class="btn btn-sm btn-light-primary align-items-center ms-3 rounded-1 hover-elevate-up">
                        Tambah
                    </a>
                    <!--end::Select-->
                </div>
                <!--end::Actions-->
            </div>
            <!--end::Controls-->
        </div>
        @if($this->layout == "grid")
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9 mb-5">
                @foreach($this->collection as $item)
                    <!--begin::Col-->
                    <div class="col-md-4">
                        <!--begin::Card-->
                        <div class="card card-flush h-md-100">
                            <!--begin::Card header-->
                            <div class="card-header ribbon ribbon-end ribbon-clip">
                                @if($item->users_count == $this->collection->max('users_count'))
                                    <div class="ribbon-label">
                                        <i class="ki-outline ki-crown fs-2 text-white"></i>
                                        <span class="ribbon-inner bg-success"></span>
                                    </div>
                                @elseif($item->users_count == $this->collection->sortByDesc('users_count')->skip(1)->first()?->users_count)
                                    <div class="ribbon-label">
                                        <i class="ki-outline ki-crown-2 fs-2 text-white"></i>
                                        <span class="ribbon-inner bg-warning"></span>
                                    </div>
                                @elseif($item->users_count == $this->collection->sortByDesc('users_count')->skip(2)->first()?->users_count)
                                    <div class="ribbon-label">
                                        <i class="ki-outline ki-cup fs-2 text-white"></i>
                                        <span class="ribbon-inner bg-info"></span>
                                    </div>
                                @endif
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>{{ $item->name }}</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-1">
                                <div class="fw-bold text-gray-600 mb-5">Jumlah Pendaftar:
                                    {{ number_format($item->users_count) }}
                                    orang
                                </div>
                                <!--begin::Permissions-->
                                <div class="d-flex flex-column text-gray-600">
                                    @php
                                        $days = [
                                            0 => 'Minggu',
                                            1 => 'Senin',
                                            2 => 'Selasa',
                                            3 => 'Rabu',
                                            4 => 'Kamis',
                                            5 => 'Jum\'at',
                                            6 => 'Sabtu'
                                        ];
                                    @endphp

                                    @foreach (($item->branchSchedules ?? collect([]))->sortBy('day_of_week') as $schedule)
                                        <div class="d-flex align-items-center py-2">
                                            <span
                                                class="bullet bg-{{ $schedule->is_open == 1 ? 'primary' : 'danger' }} me-3"></span>
                                            {{ $days[$schedule->day_of_week] }} :
                                            @if($schedule->is_open == 1)
                                                {{ date('H:i', strtotime($schedule->open_time)) }} -
                                                {{ date('H:i', strtotime($schedule->close_time)) }}
                                            @else
                                                <span class="text-muted">Tutup</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="symbol-group symbol-hover">
                                    @foreach (User::role(['Super Admin', 'Owner', 'Cabang', 'Pegawai'])->where('branch_id', $item->id)->get() as $user)
                                        <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip"
                                            data-bs-placement="top" aria-label="{{ $user->name }}" title="{{ $user->name }}">
                                            <img alt="Pic" src="{{ $user->image }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <!--end::Card body-->
                            <!--begin::Card footer-->
                            <div class="card-footer flex-wrap pt-0">
                                @role('Owner|Super Admin')
                                <a href="" wire:navigate class="btn btn-icon btn-sm btn-light-warning">
                                    <i class="ki-outline ki-notepad-edit fs-2"></i>
                                </a>
                                @if($item->st == "i")
                                    <button wire:click="active({{ $item }})" class="btn btn-icon btn-sm btn-light-success"
                                        wire:confirm="Apakah Anda yakin ingin aktifkan cabang ini?">
                                        <i class="ki-outline ki-check fs-2"></i>
                                    </button>
                                @else
                                    <button wire:click="inactive({{ $item }})" class="btn btn-icon btn-sm btn-light-danger ms-2"
                                        wire:confirm="Apakah Anda yakin ingin non aktifkan cabang ini?">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </button>
                                @endif
                @elserole('Cabang')
                                            <a href="" wire:navigate class="btn btn-icon btn-sm btn-light-success">
                                                <i class="ki-outline ki-notepad-edit fs-2"></i>
                                            </a>
                                            @endrole
                                        </div>
                                        <!--end::Card footer-->
                                    </div>
                                    <!--end::Card-->
                                </div>
                                <!--end::Col-->
                            @endforeach
            </div>
            {{ $this->collection->links() }}
        @elseif($this->layout == "list")
            <div class="card">
                <!--begin::Card body-->
                <div class="card-body py-4">
                    <!--begin::Table-->
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table_bill">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Nama</th>
                                    <th class="text-center min-w-100px">Jumlah Pendaftar</th>
                                    <th class="text-center min-w-100px">Jadwal Buka/Tutup</th>
                                    <th class="text-center min-w-100px">Status</th>
                                    <th class="text-end min-w-100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @foreach ($this->collection as $item)
                                    <tr>
                                        <td>
                                            <span class="text-gray-800 fw-bold">{{ $item->name }}</span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($item->users_count, 0, ',', '.') }}
                                            Orang
                                        </td>
                                        <td>
                                            @php
                                                $days = [
                                                    0 => 'Minggu',
                                                    1 => 'Senin',
                                                    2 => 'Selasa',
                                                    3 => 'Rabu',
                                                    4 => 'Kamis',
                                                    5 => 'Jum\'at',
                                                    6 => 'Sabtu'
                                                ];
                                            @endphp
                                            @foreach (($item->branchSchedules ?? collect([]))->sortBy('day_of_week') as $schedule)
                                                <div class="d-flex align-items-center py-2">
                                                    <span
                                                        class="bullet bg-{{ $schedule->is_open == 1 ? 'primary' : 'danger' }} me-3"></span>
                                                    {{ $days[$schedule->day_of_week] }} :
                                                    @if($schedule->is_open == 1)
                                                        {{ date('H:i', strtotime($schedule->open_time)) }} -
                                                        {{ date('H:i', strtotime($schedule->close_time)) }}
                                                    @else
                                                        <span class="text-muted">Tutup</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $item->status['class'] }} fs-7 fw-bold py-2 px-3">
                                                {{ $item->status['text'] }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @role('Owner|Super Admin')
                                            <a href="" wire:navigate class="btn btn-icon btn-sm btn-light-warning">
                                                <i class="ki-outline ki-notepad-edit fs-2"></i>
                                            </a>
                                            @if($item->st == "i")
                                                <button wire:click="active({{ $item }})"
                                                    class="btn btn-icon btn-sm btn-light-success"
                                                    wire:confirm="Apakah Anda yakin ingin aktifkan cabang ini?">
                                                    <i class="ki-outline ki-check fs-2"></i>
                                                </button>
                                            @else
                                                <button wire:click="inactive({{ $item }})"
                                                    class="btn btn-icon btn-sm btn-light-danger ms-2"
                                                    wire:confirm="Apakah Anda yakin ingin non aktifkan cabang ini?">
                                                    <i class="ki-outline ki-cross fs-2"></i>
                                                </button>
                                            @endif
                                @elserole('Cabang')
                                                    <a href="" wire:navigate class="btn btn-icon btn-sm btn-light-success">
                                                        <i class="ki-outline ki-notepad-edit fs-2"></i>
                                                    </a>
                                                    @endrole
                                                </td>
                                            </tr>
                                        @endforeach
                            </tbody>
                        </table>
                        {{ $this->collection->links() }}
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
        @endif
    </div>
</div>