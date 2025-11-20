<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, state, usesFileUploads};
use Illuminate\Support\Str;
use App\Models\UserProfile;

usesFileUploads();

state(['user' => fn() => \App\Models\User::where('id', Auth::user()->id)->first()]);
state([
    'id_card' => '',
    'family_card' => '',
    'edit_ktp' => false,
    'edit_kk' => false,
]);

mount(function () {
    $this->id_card = $this->user->profile ? $this->user->profile->id_card : '';
    $this->family_card = $this->user->profile ? $this->user->profile->family_card : '';
});
$editKtp = function() { $this->edit_ktp = true; };
$cancelKtp = function() { $this->edit_ktp = false; };
$saveKtp = function() {
    $this->validate(['id_card' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
    
    if ($this->id_card) {
        if (!$this->user->profile) {
            $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
        }
        
        $path = $this->id_card->store('id_card', 'public');
        if ($this->user->profile->id_card && Storage::disk('public')->exists($this->user->profile->id_card)) {
            Storage::disk('public')->delete($this->user->profile->id_card);
        }
        $this->user->profile->castAndUpdate(['id_card' => $path]);
    }
    $this->edit_ktp = false;
};

$editKk = function() { $this->edit_kk = true; };
$cancelKk = function() { $this->edit_kk = false; };
$saveKk = function() {
    $this->validate(['family_card' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
    
    if ($this->family_card) {
        if (!$this->user->profile) {
            $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
        }
        
        $path = $this->family_card->store('family_card', 'public');
        if ($this->user->profile->family_card && Storage::disk('public')->exists($this->user->profile->family_card)) {
            Storage::disk('public')->delete($this->user->profile->family_card);
        }
        $this->user->profile->castAndUpdate(['family_card' => $path]);
    }
    $this->edit_kk = false;
};
?>
<div>
    <div class="d-none d-lg-block">
        <div class="card mb-5 mb-xl-10" id="card_dokumen_penting">
            <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#dokumen_penting">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Dokumen Penting</h3>
                </div>
                <div class="card-toolbar rotate-180">
                    <i class="ki-duotone ki-down fs-1"></i>
                </div>
            </div>
            <div id="dokumen_penting" class="card-body p-9 collapse show">
                <!-- KTP -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">KTP</label>
                    <div class="col-lg-8">
                        @if($this->edit_ktp)
                            <div class="d-flex align-items-center">
                                <x-form-input type="file" name="id_card" class="bg-transparent" label="KTP" accept=".png,.jpg,.jpeg" wire:model="id_card"/>
                                <button wire:click="saveKtp" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelKtp" class="btn btn-icon btn-light btn-sm border-0 ms-1" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->id_card)
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('storage/' . $this->user->profile->id_card) }}" alt="KTP" class="img-fluid me-2" style="max-width: 100px; max-height: 100px;">
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editKtp" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <a wire:click="editKtp" class="text-gray-800 fs-6">Tambah KTP</a>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- Kartu Keluarga -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Kartu Keluarga</label>
                    <div class="col-lg-8">
                        @if($this->edit_kk)
                            <div class="d-flex align-items-center">
                                <x-form-input type="file" name="family_card" class="bg-transparent" label="Kartu Keluarga" accept=".png,.jpg,.jpeg" wire:model="family_card"/>
                                <button wire:click="saveKk" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelKk" class="btn btn-icon btn-light btn-sm border-0 ms-1" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->family_card)
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('storage/' . $this->user->profile->family_card) }}" alt="Kartu Keluarga" class="img-fluid me-2" style="max-width: 100px; max-height: 100px;">
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editKk" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <a wire:click="editKk" class="text-gray-800 fs-6">Tambah Kartu Keluarga</a>
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
                    <span class="fw-bold text-gray-900">Dokumen Penting</span>
                </h3>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">KTP</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_ktp)
                                <x-form-input type="file" name="id_card" class="bg-transparent" label="KTP" accept=".png,.jpg,.jpeg" wire:model="id_card"/>
                            @else
                                @if($this->user->profile?->id_card)
                                    <span class="text-gray-800 fs-6">
                                        <img src="{{ asset('storage/' . $this->user->profile->id_card) }}" alt="KTP" class="img-fluid" style="max-width: 100px; max-height: 100px;">
                                    </span>
                                @else
                                    <a wire:click="editKtp" class="text-gray-800 fs-6">
                                        Tambah KTP
                                    </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_ktp)
                                <button wire:click="saveKtp" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelKtp" class="btn btn-icon btn-light btn-sm border-0" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            @else
                                @if($this->user->profile?->id_card)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editKtp" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
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
                            <span class="text-gray-800 fw-bold fs-6">Kartu Keluarga</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_kk)
                                <x-form-input type="file" name="family_card" class="bg-transparent" label="Kartu Keluarga" accept=".png,.jpg,.jpeg" wire:model="family_card"/>
                            @else
                                @if($this->user->profile?->family_card)
                                    <span class="text-gray-800 fs-6">
                                        <img src="{{ asset('storage/' . $this->user->profile->family_card) }}" alt="KTP" class="img-fluid" style="max-width: 100px; max-height: 100px;">
                                    </span>
                                @else
                                    <a wire:click="editKk" class="text-gray-800 fs-6">
                                        Tambah kartu Keluarga
                                    </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_kk)
                                <button wire:click="saveKk" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelKk" class="btn btn-icon btn-light btn-sm border-0" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            @else
                                @if($this->user->profile?->family_card)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editKk" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
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