<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, state};
use Illuminate\Support\Str;
use App\Models\UserProfile;

state(['user' => fn() => \App\Models\User::where('id', Auth::user()->id)->first()]);
state([
    'nik' => '',
    'pob' => '',
    'bod' => '',
    'gender' => '',
    'edit_nik' => false,
    'edit_ttl' => false,
    'edit_gender' => false,
]);

mount(function () {
    $this->nik = $this->user->profile ? $this->user->profile->nik : '';
    $this->pob = $this->user->profile ? $this->user->profile->pob : '';
    $this->bod = $this->user->profile && $this->user->profile->bod ? $this->user->profile->bod->format('Y-m-d') : '';
    $this->gender = $this->user->profile ? $this->user->profile->gender : '';
});
$editNik = function() { $this->edit_nik = true; };
$saveNik = function() {
    $this->validate(['nik' => ['required', 'string', 'max:16']]);
    
    if (!$this->user->profile) {
        $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
    }
    
    $this->user->profile->castAndUpdate(['nik' => $this->nik]);
    $this->edit_nik = false;
};

$editTtl = function() { $this->edit_ttl = true; };
$saveTtl = function() {
    $this->validate([
        'pob' => ['required', 'string', 'max:255'],
        'bod' => ['required', 'date'],
    ]);
    
    if (!$this->user->profile) {
        $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
    }
    
    $this->user->profile->castAndUpdate([
        'pob' => $this->pob,
        'bod' => $this->bod,
    ]);
    $this->edit_ttl = false;
};

$editGender = function() { $this->edit_gender = true; };
$saveGender = function() {
    $this->validate(['gender' => ['required', 'in:pria,wanita']]);
    
    if (!$this->user->profile) {
        $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
    }
    
    $this->user->profile->castAndUpdate(['gender' => $this->gender]);
    $this->edit_gender = false;
};
?>
<div>
    <div class="d-none d-lg-block">
        <div class="card mb-5 mb-xl-10" id="card_info_pribadi">
            <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#info_pribadi">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Info Pribadi</h3>
                </div>
                <div class="card-toolbar rotate-180">
                    <i class="ki-duotone ki-down fs-1"></i>
                </div>
            </div>
            <div id="info_pribadi" class="card-body p-9 collapse show">
                <!-- NIK -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">NIK</label>
                    <div class="col-lg-8">
                        @if($this->edit_nik)
                            <div class="d-flex align-items-center">
                                <x-form-input type="text" name="nik" class="bg-transparent" label="NIK" placeholder="Nomor Induk Kependudukan" wire:model="nik"/>
                                <button wire:click="saveNik" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->nik)
                                <span class="fw-bold fs-6 text-gray-800 me-2">{{ $this->user->profile->nik }}</span>
                                @if($this->user->st == 'pending')
                                    <button wire:click="editNik" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @else
                                <a wire:click="editNik" class="text-gray-800 fs-6">Tambah NIK kamu</a>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- TTL -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Tempat dan Tanggal Lahir</label>
                    <div class="col-lg-8">
                        @if($this->edit_ttl)
                            <div class="row">
                                <div class="col-md-5">
                                    <x-form-input type="text" name="pob" class="bg-transparent" label="Tempat Lahir" placeholder="Tempat Lahir" wire:model="pob"/>
                                </div>
                                <div class="col-md-5">
                                    <x-form-input type="date" name="bod" class="bg-transparent" label="Tanggal Lahir" wire:model="bod"/>
                                </div>
                                <div class="col-md-2">
                                    <button wire:click="saveTtl" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                        <i class="ki-filled ki-check fs-2 text-success"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            @if($this->user->profile && $this->user->profile->pob && $this->user->profile->bod)
                                <span class="fw-semibold text-gray-800 fs-6 me-2">
                                    {{ $this->user->profile->pob . ', ' . $this->user->profile->bod->format('j F Y') }}
                                </span>
                                @if($this->user->st == 'pending')
                                    <button wire:click="editTtl" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @else
                                <a wire:click="editTtl" class="text-gray-800 fs-6">Tambah Tanggal Lahir</a>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- Gender -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Jenis Kelamin</label>
                    <div class="col-lg-8">
                        @if($this->edit_gender)
                            <div class="d-flex align-items-center">
                                <select name="gender" class="form-select bg-transparent" wire:model="gender" style="max-width: 200px;">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="pria">Pria</option>
                                    <option value="wanita">Wanita</option>
                                </select>
                                <button wire:click="saveGender" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->gender)
                                <span class="fw-semibold fs-6 text-gray-800 me-2">{{ Str::title($this->user->profile->gender) }}</span>
                                @if($this->user->st == 'pending')
                                    <button wire:click="editGender" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @else
                                <a wire:click="editGender" class="text-gray-800 fs-6">Tambah Jenis Kelamin</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-block d-lg-none">
        <div class="card card-xl-stretch mb-5 mb-xl-8">
            <div class="card-header align-items-center border-0 mt-4">
                <h3 class="card-title align-items-start flex-column">
                    <span class="fw-bold text-gray-900">Info Pribadi</span>
                </h3>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">NIK</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_nik)
                                <x-form-input type="text" name="nik" class="bg-transparent" label="NIK" placeholder="Nomor Induk Kependudukan" wire:model="nik"/>
                            @else
                                @if($this->user->profile?->nik)
                                <span class="text-gray-800 fs-6">
                                    {{ $this->user->profile?->nik }}
                                </span>
                                @else
                                <a wire:click="editNik" class="text-gray-800 fs-6">
                                    Tambah NIK kamu
                                </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_nik)
                                <button wire:click="saveNik" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->profile?->nik)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editNik" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">TTL</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_ttl)
                                <div class="row">
                                    <div class="col-md-6">
                                        <x-form-input type="text" name="pob" class="bg-transparent" label="Tempat Lahir" placeholder="Tempat Lahir" wire:model="pob"/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-form-input type="date" name="bod" class="bg-transparent" label="Tanggal Lahir" wire:model="bod"/>
                                    </div>
                                </div>
                            @else
                                @if($this->user->profile && $this->user->profile->pob && $this->user->profile->bod)
                                <span class="text-gray-800 fs-6">
                                    {{ $this->user->profile->pob . ', ' . $this->user->profile->bod->format('j F Y') }}
                                </span>
                                @else
                                <a wire:click="editTtl" class="text-gray-800 fs-6">
                                    Tambah Tanggal Lahir
                                </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_ttl)
                                <button wire:click="saveTtl" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->profile && $this->user->profile->pob && $this->user->profile->bod)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editTtl" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">Jenis Kelamin</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_gender)
                                <select name="gender" class="form-select bg-transparent" wire:model="gender">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="pria">Pria</option>
                                    <option value="wanita">Wanita</option>
                                </select>
                            @else
                                @if($this->user->profile?->gender)
                                <span class="text-gray-800 fs-6">
                                    {{ Str::title($this->user->profile->gender) }}
                                </span>
                                @else
                                <a wire:click="editGender" class="text-gray-800 fs-6">
                                    Tambah Jenis Kelamin
                                </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_gender)
                                <button wire:click="saveGender" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->profile?->gender)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editGender" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>