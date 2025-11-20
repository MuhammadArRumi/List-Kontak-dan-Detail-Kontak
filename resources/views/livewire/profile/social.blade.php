<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, state, usesFileUploads};
use Illuminate\Support\Str;
use App\Models\UserProfile;

usesFileUploads();

state(['user' => fn() => \App\Models\User::where('id', Auth::user()->id)->first()]);
state([
    'ig' => '',
    'tiktok' => '',
    'wa' => '',
    'edit_ig' => false,
    'edit_tiktok' => false,
    'edit_wa' => false,
]);

mount(function () {
    $this->ig = $this->user->profile ? $this->user->profile->ig : '';
    $this->tiktok = $this->user->profile ? $this->user->profile->tiktok : '';
    $this->wa = $this->user->profile ? $this->user->profile->wa : '';
});
$editIg = function() { $this->edit_ig = true; };
$cancelIg = function() { $this->edit_ig = false; };
$saveIg = function() {
    $this->validate(['ig' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
    
    if ($this->ig) {
        if (!$this->user->profile) {
            $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
        }
        
        $path = $this->ig->store('ig', 'public');
        if ($this->user->profile->ig && Storage::disk('public')->exists($this->user->profile->ig)) {
            Storage::disk('public')->delete($this->user->profile->ig);
        }
        $this->user->profile->castAndUpdate(['ig' => $path]);
    }
    $this->edit_ig = false;
};

$editTiktok = function() { $this->edit_tiktok = true; };
$cancelTiktok = function() { $this->edit_tiktok = false; };
$saveTiktok = function() {
    $this->validate(['tiktok' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
    
    if ($this->tiktok) {
        if (!$this->user->profile) {
            $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
        }
        
        $path = $this->tiktok->store('tiktok', 'public');
        if ($this->user->profile->tiktok && Storage::disk('public')->exists($this->user->profile->tiktok)) {
            Storage::disk('public')->delete($this->user->profile->tiktok);
        }
        $this->user->profile->castAndUpdate(['tiktok' => $path]);
    }
    $this->edit_tiktok = false;
};

$editWa = function() { $this->edit_wa = true; };
$saveWa = function() {
    $this->validate(['wa' => ['nullable', 'string', 'max:15']]);
    
    if (!$this->user->profile) {
        $this->user->profile = UserProfile::castAndCreate(['user_id' => $this->user->id]);
    }
    
    $this->user->profile->castAndUpdate(['wa' => $this->wa]);
    $this->edit_wa = false;
};
?>
<div>
    <div class="d-none d-lg-block">
        <div class="card mb-5 mb-xl-10" id="card_sosial_media">
            <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#sosial_media">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Sosial Media</h3>
                </div>
                <div class="card-toolbar rotate-180">
                    <i class="ki-duotone ki-down fs-1"></i>
                </div>
            </div>
            <div id="sosial_media" class="card-body p-9 collapse show">
                <!-- WhatsApp -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">WhatsApp</label>
                    <div class="col-lg-8">
                        @if($this->edit_wa)
                            <div class="d-flex align-items-center">
                                <x-form-input type="text" name="wa" class="bg-transparent" label="WhatsApp" placeholder="Nomor WhatsApp" wire:model="wa"/>
                                <button wire:click="saveWa" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->wa)
                                <span class="fw-bold fs-6 text-gray-800 me-2">{{ '+62' . $this->user->profile->wa }}</span>
                                @if($this->user->st == 'pending')
                                    <button wire:click="editWa" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                        <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                    </button>
                                @endif
                            @else
                                <a wire:click="editWa" class="text-gray-800 fs-6">Tambah WhatsApp</a>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- Instagram -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Instagram</label>
                    <div class="col-lg-8">
                        @if($this->edit_ig)
                            <div class="d-flex align-items-center">
                                <x-form-input type="file" name="ig" class="bg-transparent" label="Instagram" accept=".png,.jpg,.jpeg" wire:model="ig"/>
                                <button wire:click="saveIg" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelIg" class="btn btn-icon btn-light btn-sm border-0 ms-1" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->ig)
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('storage/' . $this->user->profile->ig) }}" alt="Instagram" class="img-fluid me-2" style="max-width: 100px; max-height: 100px;">
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editIg" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <a wire:click="editIg" class="text-gray-800 fs-6">Tambah Instagram</a>
                            @endif
                        @endif
                    </div>
                </div>
                
                <!-- TikTok -->
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">TikTok</label>
                    <div class="col-lg-8">
                        @if($this->edit_tiktok)
                            <div class="d-flex align-items-center">
                                <x-form-input type="file" name="tiktok" class="bg-transparent" label="TikTok" accept=".png,.jpg,.jpeg" wire:model="tiktok"/>
                                <button wire:click="saveTiktok" class="btn btn-icon btn-light btn-sm border-0 ms-2" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelTiktok" class="btn btn-icon btn-light btn-sm border-0 ms-1" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            </div>
                        @else
                            @if($this->user->profile?->tiktok)
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('storage/' . $this->user->profile->tiktok) }}" alt="TikTok" class="img-fluid me-2" style="max-width: 100px; max-height: 100px;">
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editTiktok" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
                                            <i class="ki-outline ki-pencil fs-2 text-primary"></i>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <a wire:click="editTiktok" class="text-gray-800 fs-6">Tambah TikTok</a>
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
                    <span class="fw-bold text-gray-900">Sosial Media</span>
                </h3>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-center mb-7">
                    <div class="d-flex flex-row-fluid align-items-center">
                        <div class="w-25">
                            <span class="text-gray-800 fw-bold fs-6">WhatsApp</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_wa)
                                <x-form-input type="text" name="wa" class="bg-transparent" label="WhatsApp" placeholder="Nomor WhatsApp" wire:model="wa"/>
                            @else
                                @if($this->user->profile?->wa)
                                <span class="text-gray-800 fs-6">
                                    {{ '+62' . $this->user->profile->wa }}
                                </span>
                                @else
                                <a wire:click="editWa" class="text-gray-800 fs-6">
                                    Tambah WhatsApp
                                </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_wa)
                                <button wire:click="saveWa" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                            @else
                                @if($this->user->profile?->wa)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editWa" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
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
                            <span class="text-gray-800 fw-bold fs-6">Instagram</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_ig)
                                <x-form-input type="file" name="ig" class="bg-transparent" label="Instagram" accept=".png,.jpg,.jpeg" wire:model="ig"/>
                            @else
                                @if($this->user->profile?->ig)
                                    <span class="text-gray-800 fs-6">
                                        <img src="{{ asset('storage/' . $this->user->profile->ig) }}" alt="KTP" class="img-fluid" style="max-width: 100px; max-height: 100px;">
                                    </span>
                                @else
                                    <a wire:click="editIg" class="text-gray-800 fs-6">
                                        Tambah Instagram
                                    </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_ig)
                                <button wire:click="saveIg" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelIg" class="btn btn-icon btn-light btn-sm border-0" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            @else
                                @if($this->user->profile?->ig)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editIg" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
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
                            <span class="text-gray-800 fw-bold fs-6">TikTok</span>
                        </div>
                        <div class="w-50">
                            @if($this->edit_tiktok)
                                <x-form-input type="file" name="tiktok" class="bg-transparent" label="TikTok" accept=".png,.jpg,.jpeg" wire:model="tiktok"/>
                            @else
                                @if($this->user->profile?->tiktok)
                                    <span class="text-gray-800 fs-6">
                                        <img src="{{ asset('storage/' . $this->user->profile->tiktok) }}" alt="KTP" class="img-fluid" style="max-width: 100px; max-height: 100px;">
                                    </span>
                                @else
                                    <a wire:click="editTiktok" class="text-gray-800 fs-6">
                                        Tambah Tiktok
                                    </a>
                                @endif
                            @endif
                        </div>
                        <div class="w-25 text-end">
                            @if($this->edit_tiktok)
                                <button wire:click="saveTiktok" class="btn btn-icon btn-light btn-sm border-0" aria-label="Save">
                                    <i class="ki-filled ki-check fs-2 text-success"></i>
                                </button>
                                <button wire:click="cancelTiktok" class="btn btn-icon btn-light btn-sm border-0" aria-label="Batalkan">
                                    <i class="ki-filled ki-cross fs-2 text-danger"></i>
                                </button>
                            @else
                                @if($this->user->profile?->tiktok)
                                    @if($this->user->st == 'pending')
                                        <button wire:click="editTiktok" class="btn btn-icon btn-light btn-sm border-0" aria-label="Edit">
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