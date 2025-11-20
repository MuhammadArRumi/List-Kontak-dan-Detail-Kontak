<?php
use Livewire\Volt\Component;
use App\Models\Master\Brand;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $brands;
    public function mount()
    {
        $this->brands = Brand::where('st', 'a')
        ->withCount('products')
        ->orderBy('name')
        ->get();
    }
    public function placeholder()
    {
        return view('livewire.skeleton.brand-home');
    }
};
?>
<div class="mb-12" id="brands">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6">
        <div class="position-relative pb-2 mb-4 mb-md-0">
            <h2 class="fw-bold text-gray-900 mb-0 fs-2">Merek Pilihan</h2>
            <div class="position-absolute bottom-0 start-0 w-50 h-2px bg-primary rounded"></div>
        </div>
    </div>
    
    <div class="row g-3 g-md-4">
        @foreach($this->brands as $brand)
        <div class="col-4 col-sm-3 col-md-2 col-lg-2 col-xl-1-5">
            <a href="{{ route('brand.show', ['brand' => $brand]) }}" wire:navigate 
                class="card card-brand h-100 border-0 shadow-sm rounded-3 overflow-hidden text-decoration-none text-dark">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                    <div class="position-relative mb-3">
                        <div class="bg-light rounded-circle p-3 d-flex align-items-center justify-content-center" 
                            style="width: 80px; height: 80px;">
                            <img src="{{ $brand->image ?? asset('media/placeholder/brand.png') }}" 
                                class="img-fluid object-fit-contain" 
                                alt="{{ $brand->name }}"
                                style="max-width: 50px; max-height: 50px;"
                                loading="lazy">
                        </div>
                    </div>
                    <div class="text-center">
                        <h5 class="text-gray-800 fw-bold mb-0 fs-6 truncate-2">{{ $brand->name }}</h5>
                        <span class="text-muted fs-7 mt-1 d-block">{{ $brand->products_count }} produk</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>