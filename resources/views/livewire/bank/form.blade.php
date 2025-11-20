<?php

    use App\Models\Master\Bank;
    use App\Models\UserBank;
    use function Livewire\Volt\mount;
    use function Livewire\Volt\rules;
    use function Livewire\Volt\state;
    use Illuminate\Support\Facades\Auth;

    state(['bank'])->locked();

    // Form states
    state([
        'banks'            => '',
        'nomor_rekening'   => '',
        'pemilik_rekening' => '',
    ]);

    // Validation rules
    rules(fn() => [
        'banks'            => 'required',
        'nomor_rekening'   => 'required|numeric',
        'pemilik_rekening' => 'required',
    ]);

    // Initialize form with existing address data if editing
    mount(function () {
        if ($this->bank) {
            $this->banks            = $this->bank->bank_id;
            $this->nomor_rekening   = $this->bank->account_number;
            $this->pemilik_rekening = $this->bank->account_holder;
        }
    });

    // Save address
    $saveBank = function () {
        $this->dispatch('toast-error', message: $this->validate());

        $data = [
            'user_id'        => Auth::user()->id,
            'bank_id'        => $this->banks,
            'account_number' => $this->nomor_rekening,
            'account_holder' => $this->pemilik_rekening,
        ];

        try {
            if ($this->bank) {
                $this->bank->castAndUpdate($data);
            } else {
                UserBank::castAndCreate($data);
            }

            $this->dispatch('toast-success', message: "Bank berhasil disimpan");
            $this->redirect(route('profile.bank'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast-error', message: "Gagal menyimpan Bank: " . $e->getMessage());
        }
    };
?>

<div class="card d-flex flex-row-fluid flex-center mb-10">
    <x-form action="saveBank" class="card-body w-100" id="form_bank">
        <div class="w-100">
            <!-- Status Kepemilikan Rumah -->
            <div class="row mb-5">
                <!-- Propinsi -->
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="banks" label="Bank" required>
                        <x-form-select
                            name="banks"
                            class="form-select form-select-solid fw-bold"
                            :options="Bank::pluck('name', 'id')
                                ->prepend('Pilih bank', '')"
                            modifier="change"
                        />
                    </x-form-group>
                </div>
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="nomor_rekening" label="Nomor Rekening" required>
                        <x-form-input
                            type="text"
                            name="nomor_rekening"
                            class="bg-transparent"
                        />
                    </x-form-group>
                </div>
                <div class="col-12 col-md-4 mb-5">
                    <x-form-group name="pemilik_rekening" label="Pemilik Rekening" required>
                        <x-form-input
                            type="text"
                            name="pemilik_rekening"
                            class="bg-transparent"
                        />
                    </x-form-group>
                </div>

            </div>

            <!-- Kecamatan, Kelurahan, Kode Pos -->

        </div>
        <!-- Submit Button -->
        <x-button class="btn btn-success btn-block w-100 mt-3" id="tombol_simpan_bank" submit="true" indicator="Harap tunggu..." label="Simpan Rekening" />
    </x-form>
    <livewire:modal.toc/>
</div>
