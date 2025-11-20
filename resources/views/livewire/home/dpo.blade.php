<?php
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    #[Computed]
    public function dpos(){
        return User::onlyBanned()->get();
    }
};
?>
<div class="mt-10 mb-10">
    <div class="text-center mb-10">
        <h2 class="fw-bold fs-2hx text-gray-900 mb-5">Daftar Pencarian Orang (DPO)</h2>
        <div class="fs-5 text-muted fw-semibold">
            <p class="mb-0">Orang-orang yang membawa kabur barang sewa dan sedang dalam pencarian</p>
        </div>
    </div>

    @if($this->dpos->count() > 0)
    <div class="tns tns-default" wire:ignore style="direction: ltr">
        <div data-tns="true" data-tns-loop="true" data-tns-swipe-angle="false" data-tns-speed="2000" data-tns-autoplay="true" data-tns-autoplay-timeout="18000" data-tns-controls="true" data-tns-nav="false" data-tns-items="1" data-tns-center="false" data-tns-dots="false" data-tns-prev-button="#dpo_prev" data-tns-next-button="#dpo_next" data-tns-responsive="{1200: {items: 3}, 992: {items: 2}}">
            @foreach ($this->dpos as $dpo)
            <div class="text-center px-5">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="octagon mx-auto mb-5 d-flex w-200px h-200px bgi-no-repeat bgi-size-contain bgi-position-center" style="background-image:url('{{ $dpo->profile->image }}')"></div>
                        <div class="mb-5">
                            <h3 class="text-gray-900 fw-bold text-hover-primary fs-3 mb-2">{{ $dpo->name }}</h3>
                            <div class="text-danger fw-semibold fs-5">Status: DPO</div>
                        </div>
                        <button class="btn btn-sm btn-light-danger fw-bold" data-bs-toggle="modal" data-bs-target="#reportModal" data-dpo-id="{{ $dpo->id }}">
                            <i class="ki-outline ki-information fs-2 me-2"></i> Laporkan Penemuan
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button class="btn btn-icon btn-active-color-primary position-absolute top-50 start-0 translate-middle-y" id="dpo_prev">
            <i class="ki-outline ki-left fs-2x"></i>
        </button>
        <button class="btn btn-icon btn-active-color-primary position-absolute top-50 end-0 translate-middle-y" id="dpo_next">
            <i class="ki-outline ki-right fs-2x"></i>
        </button>
    </div>
    @else
    <div class="text-center py-10">
        <img src="{{ asset('assets/media/illustrations/sigma-1/13.png') }}" class="w-200px mb-5 theme-light-show" alt="No DPO">
        <img src="{{ asset('assets/media/illustrations/sigma-1/13-dark.png') }}" class="w-200px mb-5 theme-dark-show" alt="No DPO">
        <h3 class="text-gray-600">Tidak ada DPO saat ini</h3>
        <p class="text-muted">Semua pelanggan telah mengembalikan barang sewa dengan baik</p>
    </div>
    @endif
</div>