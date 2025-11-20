<?php
use function Livewire\Volt\{computed, mount, rules, state};
use App\Models\{Country, State, City, Subdistrict, Village, UserFamily};
use Illuminate\Support\Facades\Auth;

state([
    'role' => fn() => Auth::user()->getRoleNames()[0]
]);
state(['family'])->locked();

// Form states
state([
    'status_keluarga' => '',
    'nama' => '',
    'no_hp' => '',
    'tag' => '',
    'setuju' => false,
]);

// Validation rules
rules(fn () => [
    'status_keluarga' => 'required|string|max:255',
    'nama' => 'required|string|max:255',
    'no_hp' => 'required|string|max:20|regex:/^[0-9]+$/',
    'setuju' => 'required|accepted',
]);

// Initialize form with existing Family data if editing
mount(function () {
    if ($this->family) {
        $this->status_keluarga = $this->family->type;
        $this->nama = $this->family->name;
        $this->no_hp = $this->family->phone;
        $this->tag = $this->family->tags;
    }
});

// Check if all required fields are filled
$isFormComplete = computed(function () {
    return $this->status_keluarga &&
           $this->nama &&
           $this->no_hp &&
           ($this->role == 'Konsumen' || $this->role == 'Onboarding' ? $this->setuju : true);
});
// Save Family
$saveKeluarga = function() {
    $validated = $this->validate();
    
    $data = [
        'type' => $this->status_keluarga,
        'name' => $this->nama,
        'phone' => $this->no_hp,
        'tags' => $this->tag
    ];
    
    if($this->role == "Konsumen" || $this->role == "Onboarding"){
        $data['user_id'] = Auth::user()->id;
    }

    try {
        if ($this->family) {
            $this->family->castAndUpdate($data);
            $message = "Data keluarga berhasil diperbarui";
        } else {
            UserFamily::castAndCreate($data);
            $message = "Keluarga berhasil ditambahkan";
        }
        
        $this->dispatch('toast-success', message: $message);
        $this->redirect(route('profile.family'), navigate: true);
    } catch (\Exception $e) {
        $this->dispatch('toast-error', message: "Gagal menyimpan: " . $e->getMessage());
    }
};
?>
<div class="card shadow-sm">
    <div class="card-header bg-light-primary">
        <h3 class="card-title fw-bold text-gray-800">
            <i class="bi bi-person-plus-fill me-2"></i>
            {{ $family ? 'Edit Data Keluarga' : 'Form Tambah Keluarga' }}
        </h3>
    </div>
    
    <x-form action="saveKeluarga" class="card-body" id="form_keluarga">
        <div class="row g-5">
            <!-- Status Keluarga -->
            <div class="col-12 col-md-4">
                <x-form-group name="status_keluarga" label="Status dalam Keluarga" required>
                    <x-form-select 
                        name="status_keluarga"
                        class="form-select"
                        :options="[
                            '' => 'Pilih status',
                            'Kepala Keluarga' => 'Kepala Keluarga',
                            'Suami' => 'Suami',
                            'Istri' => 'Istri',
                            'Anak' => 'Anak',
                            'Menantu' => 'Menantu',
                            'Cucu' => 'Cucu',
                            'Orangtua' => 'Orangtua',
                            'Mertua' => 'Mertua',
                            'Famili Lain' => 'Famili Lain',
                            'Pembantu' => 'Pembantu',
                            'Lainnya' => 'Lainnya',
                        ]"
                    />
                </x-form-group>
            </div>
            
            <!-- Nama -->
            <div class="col-12 col-md-4">
                <x-form-group name="nama" label="Nama Lengkap" required>
                    <x-form-input-group>
                        <x-form-input 
                            type="text" 
                            name="nama"
                            placeholder="John Doe"
                            autocomplete="text"
                        />
                        <button 
                            type="button" 
                            class="btn btn-icon btn-light-primary"
                            title="Ambil dari kontak"
                            onclick="pickContacts()"
                        >
                            <i class="bi bi-person-lines-fill"></i>
                        </button>
                    </x-form-input-group>
                </x-form-group>
            </div>

            <!-- No HP -->
            <div class="col-12 col-md-4">
                <x-form-group label="No HP" required>
                    <x-form-input-group>
                        <x-form-input-group-text>+62</x-form-input-group-text>
                        <x-form-input 
                            type="text" 
                            name="no_hp"
                            class="bg-transparent" 
                            placeholder="8123456789"
                        >
                            @slot('help')
                            <small class="form-text text-muted">
                                Masukkan nomor HP keluarga Anda tanpa angka 0 atau +62 dan - pada form diatas sesuai dengan contoh.
                            </small>
                            @endslot
                        </x-form-input>
                    </x-form-input-group>
                </x-form-group>
            </div>
            
            @role('Super Admin|Owner|Cabang|Pegawai')
            <!-- Tag -->
            <div class="col-12 col-md-4">
                <x-form-group name="tag" label="Tag Keluarga">
                    <x-form-input 
                        type="text" 
                        name="tag" 
                        placeholder="Masukkan tag"
                    />
                </x-form-group>
            </div>
            @endrole
            
            @role('Konsumen|Onboarding')
            <!-- Terms Agreement -->
            <div class="col-12">
            <x-form-group>
                <x-form-input-group class="mt-8">
                    <x-form-checkbox id="setuju" modifier="change" name="setuju" label="Saya telah membaca dan setuju dengan">
                    @slot('help')
                        <a style="font-weight:bold" href="#" data-bs-toggle="modal" data-bs-target="#ModalSnK">Syarat dan Ketentuan {{ config('app.name') }}</a>
                    @endslot
                    </x-form-checkbox>
                </x-form-input-group>
            </x-form-group>
            </div>
            @endrole
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-3 mt-8 pt-5 border-top">
            <x-button 
                tag="a" 
                class="btn-light" 
                label="Batal" 
                href="{{route('profile.family')}}"
            />
            <x-button 
                submit="true" 
                class="btn-primary" 
                :disabled="!$this->isFormComplete" 
                indicator="menyimpan..."
                :label="$family ? 'Simpan Perubahan' : 'Tambah Keluarga'"
            />
        </div>
    </x-form>
    <!-- Terms and Conditions Modal -->
    <livewire:modal.toc/>
</div>

@section('custom_js')
<script data-navigate-once>
    // $('[data-control="select2"]').select2({
    //     dropdownParent: $('#form_keluarga'),
    //     width: '100%'
    // }).on('change', function(e) {
    //     @this.set('status_keluarga', $(this).val());
    // });
    
    // Format phone number input
    $('#no_hp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    // Fungsi untuk mengatur readonly berdasarkan dukungan kontak
    function setInputReadonly() {
        const isContactSupported = navigator.contacts && window.ContactsManager;
        const namaInput = document.getElementById('nama');
        const noHpInput = document.getElementById('no_hp');
        
        if (namaInput && noHpInput) {
            namaInput.readOnly = isContactSupported;
            noHpInput.readOnly = isContactSupported;
        }
    }

    // Panggil saat halaman dimuat
    setInputReadonly();
    
    // Panggil ulang saat navigasi Livewire
    document.addEventListener('livewire:navigated', setInputReadonly);
    // Contact picker functionality
    async function pickContacts() {
        if (!navigator.contacts || !window.ContactsManager) {
            console.log("Contact Picker API not supported");
            return;
        }
        
        try {
            const properties = ['name', 'tel'];
            const contacts = await navigator.contacts.select(properties, {multiple: true});
            
            if (contacts && contacts.length > 0) {
                const firstContact = contacts[0];
                if (firstContact.name && firstContact.name.length > 0) {
                    @this.set('nama', firstContact.name[0]);
                }
                if (firstContact.tel && firstContact.tel.length > 0) {
                    let phone = firstContact.tel[0].replace(/\D/g, '');
                    if (phone.startsWith('62')) {
                        phone = phone.substring(2);
                    } else if (phone.startsWith('0')) {
                        phone = phone.substring(1);
                    }
                    @this.set('no_hp', phone);
                }
                
                // Set kembali readonly setelah memilih kontak
                setInputReadonly();
            }
        } catch (error) {
            console.error("Error picking contacts:", error);
        }
    }
</script>
@endsection