<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, state, usesFileUploads};
use App\Models\UserProfile; // Import UserProfile model for creating profiles

usesFileUploads();
state(['user' => fn() => \App\Models\User::where('id', Auth::user()->id)->first()]);
state([
    'nama' => '',
    'email' => '',
    'phone' => '',
    
    'edit_nama' => false,
    'edit_email' => false,
    'edit_phone' => false,
]);
$editNama = function() { $this->edit_nama = true; };
$saveNama = function() {
    $this->validate(['nama' => ['required', 'string', 'max:255']]);
    $this->user->castAndUpdate(['name' => $this->nama]);
    $this->edit_nama = false;
};

$editEmail = function() { $this->edit_email = true; };
$saveEmail = function() {
    $this->validate(['email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id]]);
    $this->user->castAndUpdate(['email' => $this->email]);
    $this->edit_email = false;
};

$editPhone = function() { $this->edit_phone = true; };
$savePhone = function() {
    $this->validate(['phone' => ['required', 'string', 'max:15', 'unique:users,phone,' . $this->user->id]]);
    $this->user->castAndUpdate(['phone' => $this->phone]);
    $this->edit_phone = false;
};
?>
<div>
    <div class="d-none d-lg-block">
        <div class="card mb-5 mb-xl-10" id="card_info_profil">
            <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#info_profil">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Info Profil</h3>
                </div>
                <div class="card-toolbar rotate-180">
                    <i class="ki-duotone ki-down fs-1"></i>
                </div>
            </div>
            <div id="info_profil" class="card-body p-9 collapse show">
                <!-- Nama -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Nama</label>
                    <div class="col-lg-8">
                        @if($this->edit_nama)
                            <div class="d-flex align-items-center">
                                <x-form-input type="text" name="nama" class="bg-transparent" label="Nama Lengkap" placeholder="Nama Lengkap Anda" wire:model="nama"/>
                                <button wire:click="saveNama" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            <span class="fw-bold fs-6 text-gray-800 me-2">{{ $this->user->name ?? 'Belum diisi' }}</span>
                            @if($this->user->st == 'pending')
                                <button wire:click="editNama" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                    <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- Email -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Email</label>
                    <div class="col-lg-8">
                        @if($this->edit_email)
                            <div class="d-flex align-items-center">
                                <x-form-input type="email" name="email" class="bg-transparent" label="Email" placeholder="Alamat Email Anda" wire:model="email"/>
                                <button wire:click="saveEmail" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            <span class="fw-semibold text-gray-800 fs-6 me-2">{{ $this->user->email ?? 'Belum diisi' }}</span>
                            @if($this->user->st == 'pending')
                                <button wire:click="editEmail" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                    <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Nomor HP</label>
                    <div class="col-lg-8">
                        @if($this->edit_phone)
                            <div class="d-flex align-items-center">
                                <x-form-input type="text" name="phone" class="bg-transparent" label="Nomor HP" placeholder="Nomor HP Anda" wire:model="phone"/>
                                <button wire:click="savePhone" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            <span class="fw-bold fs-6 text-gray-800 me-2">{{ $this->user->phone ? '+62' . $this->user->phone : 'Belum diisi' }}</span>
                            @if($this->user->st == 'pending')
                                <button wire:click="editPhone" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                    <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                </button>
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
                    <span class="fw-bold text-gray-900">Info Profil</span>
                </h3>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">Nama</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_nama)
                                <x-form-input type="text" name="nama" class="bg-transparent" label="Nama Lengkap" placeholder="Nama Lengkap Anda" wire:model="nama"/>
                            @else
                                <span class="text-gray-800 fs-6">
                                    {{ $this->user->name ?? 'Belum diisi' }}
                                </span>
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_nama)
                                <button wire:click="saveNama" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->st == 'pending')
                                    <button wire:click="editNama" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">Email</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_email)
                                <x-form-input type="email" name="email" class="bg-transparent" label="Email" placeholder="Alamat Email Anda" wire:model="email"/>
                            @else
                                <span class="text-gray-800 fs-6">
                                    {{ $this->user->email ?? 'Belum diisi' }}
                                </span>
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_email)
                                <button wire:click="saveEmail" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->st == 'pending')
                                    <button wire:click="editEmail" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">Nomor HP</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_phone)
                                <x-form-input type="text" name="phone" class="bg-transparent" label="Nomor HP" placeholder="Nomor HP Anda" wire:model="phone"/>
                            @else
                                <span class="text-gray-800 fs-6">
                                    {{ $this->user->phone ? '+62' . $this->user->phone : 'Belum diisi' }}
                                </span>
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_phone)
                                <button wire:click="savePhone" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->st == 'pending')
                                    <button wire:click="editPhone" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-arrow-right fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>