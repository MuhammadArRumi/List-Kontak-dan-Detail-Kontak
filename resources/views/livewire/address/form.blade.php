<?php
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{computed, mount, rules, state};
use App\Models\Region\{Country, State, City, Subdistrict, Village};

state(['address'])->locked();

// Form states
state([
    'status_kepemilikan_rumah' => '',
    'propinsi' => '',
    'kota' => '',
    'kecamatan' => '',
    'kelurahan' => '',
    'kode_pos' => '',
    'alamat' => '',
    'catatan' => '',
    'jadikan_alamat_utama' => false,
    'setuju' => false,
    'lat' => '',
    'lng' => '',
]);

// Validation rules
rules(fn () => [
    'status_kepemilikan_rumah' => 'required|string|max:255',
    'propinsi' => 'required|numeric|exists:states,id',
    'kota' => 'required|numeric|exists:cities,id',
    'kecamatan' => 'required|numeric|exists:subdistricts,id',
    'kelurahan' => 'required|numeric|exists:villages,id',
    'kode_pos' => 'required|digits:5',
    'alamat' => 'required|string|max:255',
    'catatan' => 'required|string|max:255',
    'setuju' => 'required|accepted',
]);

// Initialize form with existing address data if editing
mount(function () {
    if ($this->address) {
        $this->status_kepemilikan_rumah = $this->address->label;
        $this->propinsi = $this->address->state_id;
        $this->kota = $this->address->city_id;
        $this->kecamatan = $this->address->subdistrict_id;
        $this->kelurahan = $this->address->village_id;
        $this->kode_pos = $this->address->postal_code;
        $this->alamat = $this->address->address;
        $this->catatan = $this->address->notes;
        $this->jadikan_alamat_utama = (bool)$this->address->is_primary;
        $this->lat = $this->address->latitude;
        $this->lng = $this->address->longitude;
    }
});

// Check if all required fields are filled
$isFormComplete = computed(function () {
    return $this->status_kepemilikan_rumah &&
           $this->propinsi &&
           $this->kota &&
           $this->kecamatan &&
           $this->kelurahan &&
           $this->kode_pos &&
           $this->alamat &&
           $this->catatan &&
           $this->setuju;
});

// Location handlers
$setLatitude = function($value) { $this->lat = $value; };
$setLongitude = function($value) { $this->lng = $value; };

// Form field updated handlers
$updatedPropinsi = function() { $this->reset(['kota', 'kecamatan', 'kelurahan', 'kode_pos']); };
$updatedKota = function() { $this->reset(['kecamatan', 'kelurahan', 'kode_pos']); };
$updatedKecamatan = function() { $this->reset(['kelurahan', 'kode_pos']); };
$updatedKelurahan = function() {
    if ($this->kelurahan) {
        $this->kode_pos = Village::find($this->kelurahan)->poscode;
    }
};

// Save address
$saveAlamat = function() {
    $this->dispatch('toast-error', message: $this->validate());
    
    $isDefault = !UserAddress::where('user_id', Auth::id())->exists() || $this->jadikan_alamat_utama;
    
    if ($isDefault) {
        UserAddress::where('user_id', Auth::id())->update(['is_primary' => false]);
    }

    $data = [
        'user_id' => Auth::user()->id,
        'label' => $this->status_kepemilikan_rumah,
        'state_id' => $this->propinsi,
        'city_id' => $this->kota,
        'subdistrict_id' => $this->kecamatan,
        'village_id' => $this->kelurahan,
        'postal_code' => $this->kode_pos,
        'address' => $this->alamat,
        'notes' => $this->catatan,
        'is_primary' => $isDefault,
        'lat' => $this->lat,
        'lng' => $this->lng,
    ];

    try {
        if ($this->address) {
            $this->address->castAndUpdate($data);
        } else {
            UserAddress::castAndCreate($data);
        }
        
        $this->dispatch('toast-success', message: "Alamat berhasil disimpan");
        $this->redirect(route('profile.address'), navigate: true);
    } catch (\Exception $e) {
        $this->dispatch('toast-error', message: "Gagal menyimpan alamat: " . $e->getMessage());
    }
};
?>

<div class="card d-flex flex-row-fluid flex-center mb-10">
    <x-form action="saveAlamat" class="card-body w-100" id="form_alamat">
        <div class="w-100">
            <!-- Status Kepemilikan Rumah -->
            <div class="row mb-5">
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="status_kepemilikan_rumah" label="Status Kepemilikan Rumah" required>
                        <x-form-select 
                            name="status_kepemilikan_rumah"
                            class="form-select form-select-solid fw-bold"
                            :options="[
                                '' => 'Pilih Status Kepemilikan Rumah',
                                'Rumah Pribadi' => 'Rumah Pribadi',
                                'Rumah Orangtua' => 'Rumah Orangtua',
                                'Apartment' => 'Apartment',
                                'Kantor' => 'Kantor',
                                'Kost/Kontrakan' => 'Kost/Kontrakan'
                            ]"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <!-- Propinsi -->
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="propinsi" label="Propinsi" required>
                        <x-form-select 
                            name="propinsi" 
                            class="form-select form-select-solid fw-bold"
                            :options="State::where('country_id', 103)
                                ->pluck('name', 'id')
                                ->prepend('Pilih Propinsi', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <!-- Kota -->
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="kota" label="Kota" required :disabled="!$this->propinsi">
                        <x-form-select 
                            name="kota" 
                            class="form-select form-select-solid fw-bold"
                            :options="City::where('state_id', $this->propinsi)
                                ->get(['id', 'name', 'type'])
                                ->mapWithKeys(fn ($item) => [$item->id => $item->type . ' ' . $item->name])
                                ->prepend('Pilih Kota', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
            </div>
            
            <!-- Kecamatan, Kelurahan, Kode Pos -->
            <div class="row mb-5">
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="kecamatan" label="Kecamatan" required :disabled="!$this->kota">
                        <x-form-select 
                            name="kecamatan" 
                            class="form-select form-select-solid fw-bold"
                            :options="Subdistrict::where('city_id', $this->kota)
                                ->pluck('name', 'id')
                                ->prepend('Pilih Kecamatan', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="kelurahan" label="Kelurahan" required :disabled="!$this->kecamatan">
                        <x-form-select 
                            name="kelurahan" 
                            class="form-select form-select-solid fw-bold"
                            :options="Village::where('subdistrict_id', $this->kecamatan)
                                ->pluck('name', 'id')
                                ->prepend('Pilih Kelurahan', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="kode_pos" label="Kode Pos" required>
                        <x-form-input 
                            type="text" 
                            name="kode_pos" 
                            readonly 
                            class="bg-transparent" 
                            wire:model="kode_pos"
                        />
                    </x-form-group>
                </div>
            </div>
            
            <!-- Alamat dan Catatan -->
            <div class="row mb-5">
                <div class="col-12 col-md-6 mb-5">
                    <x-form-group name="alamat" label="Alamat" required>
                        <x-form-textarea 
                            name="alamat" 
                            class="bg-transparent" 
                            rows="3"
                            modifier="blur"
                        />
                    </x-form-group>
                </div>
                
                <div class="col-12 col-md-6 mb-5">
                    <x-form-group name="catatan" label="Catatan" required>
                        <x-form-textarea 
                            name="catatan" 
                            class="bg-transparent" 
                            rows="3"
                            modifier="blur"
                        />
                    </x-form-group>
                </div>
            </div>
            
            <!-- Options -->
            <div class="row mb-5">
                @if(UserAddress::where('user_id', Auth::id())->exists())
                <div class="col-12 col-md-6 mb-5">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="flexSwitchDefault"
                            wire:model="jadikan_alamat_utama"
                        />
                        <label class="form-check-label" for="flexSwitchDefault">
                            Jadikan alamat utama
                        </label>
                    </div>
                </div>
                @endif
                
                <div class="col-12 col-md-6 mb-5">
                    <x-form-input-group class="mt-8">
                        <x-form-checkbox id="setuju" modifier="change" name="setuju" label="Saya telah membaca dan setuju dengan">
                        @slot('help')
                            <a style="font-weight:bold" href="#" data-bs-toggle="modal" data-bs-target="#ModalSnK">Syarat dan Ketentuan {{ config('app.name') }}</a>
                        @endslot
                        </x-form-checkbox>
                    </x-form-input-group>
                </div>
            </div>
            
            <input type="hidden" wire:model="lat">
            <input type="hidden" wire:model="lng">
        </div>
        <!-- Submit Button -->
        <x-button class="btn btn-success btn-block w-100 mt-3" :disabled="!$this->isFormComplete" id="tombol_simpan_alamat" submit="true" indicator="Harap tunggu..." label="Simpan Alamat" />
    </x-form>
    <livewire:modal.toc/>
</div>

@section('custom_js')
<script data-navigate-once>
    function requestLocation(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    @this.setLatitude(position.coords.latitude);
                    @this.setLongitude(position.coords.longitude);
                },
                (error) => {
                    console.error("Error getting location:", error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        requestLocation();
    });
    document.addEventListener('livewire:navigated', function() {
        requestLocation();
    });
</script>
@endsection