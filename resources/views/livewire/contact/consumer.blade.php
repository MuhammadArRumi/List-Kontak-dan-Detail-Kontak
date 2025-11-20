<?php
use App\Models\Contact;
use function Livewire\Volt\{rules, state};

state(['nama', 'email', 'subjek', 'pesan']);
rules(fn () => [
    'nama' => 'required|string|max:255',
    'email' => 'required|email|max:255',
    'subjek' => 'required|string|max:255',
    'pesan' => 'required|string|max:1000',
]);

$send = function() {
    $this->validate();
    Contact::create([
        'name' => $this->nama,
        'email' => $this->email,
        'subject' => $this->subjek,
        'message' => $this->pesan,
    ]);
    $this->reset();
    session()->flash('message', 'Pesan berhasil dikirim!');
};
?>

<div class="card">
    <div class="card-body p-lg-17">
        @if(session('message'))
            <div class="alert alert-success mb-9">
                {{ session('message') }}
            </div>
        @endif
        
        <div class="row mb-3">
            <div class="col-md-6 pe-lg-10">
                <form wire:submit="send" class="form mb-15">
                    <h1 class="fw-bold text-gray-900 mb-9">Kirim Kami Pesan</h1>
                    <div class="row mb-5">
                        <div class="col-md-6 fv-row">
                            <div class="mb-5">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" wire:model="nama" class="form-control bg-transparent" placeholder="Masukkan nama lengkap">
                                @error('nama') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6 fv-row">
                            <div class="mb-5">
                                <label class="form-label">Email Anda</label>
                                <input type="email" wire:model="email" class="form-control bg-transparent" placeholder="Masukkan email">
                                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column mb-5 fv-row">
                        <div class="mb-5">
                            <label class="form-label">Subjek</label>
                            <input type="text" wire:model="subjek" class="form-control bg-transparent" placeholder="Masukkan subjek">
                            @error('subjek') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="d-flex flex-column mb-10 fv-row">
                        <div class="mb-10">
                            <label class="form-label">Pesan Anda</label>
                            <textarea wire:model="pesan" rows="6" class="form-control bg-transparent" placeholder="Tulis pesan Anda"></textarea>
                            @error('pesan') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Kirim</span>
                        <span wire:loading>Harap tunggu...</span>
                    </button>
                </form>
            </div>
            <div class="col-md-6 ps-lg-10">
                <div wire:ignore id="map_adamasanya" class="w-100 rounded mb-2 mb-lg-0 mt-2" style="height: 486px"></div>
            </div>
        </div>
        <div class="row g-5 mb-5 mb-lg-15">
            <div class="col-sm-6 pe-lg-10">
                <div class="bg-light card-rounded d-flex flex-column flex-center flex-center p-10 h-100">
                    <i class="ki-outline ki-briefcase fs-3tx text-primary"></i>
                    <h1 class="text-gray-900 fw-bold my-5">Let's Speak</h1>
                    <div class="text-gray-700 fw-semibold fs-2">+62 877-6534-6368</div>
                </div>
            </div>
            <div class="col-sm-6 ps-lg-10">
                <div class="text-center bg-light card-rounded d-flex flex-column flex-center p-10 h-100">
                    <i class="ki-outline ki-geolocation fs-3tx text-primary"></i>
                    <h1 class="text-gray-900 fw-bold my-5">Our Head Office</h1>
                    <div class="text-gray-700 fs-3 fw-semibold">Jl. Muara Takus Raya Jl. Trowulan No.21A</div>
                </div>
            </div>
        </div>
        <div class="card mb-4 bg-light text-center">
            <div class="card-body py-12">
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/facebook-4.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/instagram-2-1.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/github.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/behance.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/pinterest-p.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/twitter.svg')}}" class="h-30px my-2" alt="" />
                </a>
                <a href="#" class="mx-4">
                    <img src="{{asset('assets/media/svg/brand-logos/dribbble-icon-1.svg')}}" class="h-30px my-2" alt="" />
                </a>
            </div>
        </div>
    </div>
</div>  