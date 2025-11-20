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
        ->limit(6)->get();
    }
    public function placeholder()
    {
        return view('livewire.skeleton.brand-home');
    }
};
?>
<div class="row home-brand">
    @foreach ($this->brands as $brand)
    <div class="col-3 col-md-3 col-lg-2">
        <a href="{{ route('brand.show',['brand' => $brand]) }}" wire:navigate class="card hover-elevate-up shadow-sm parent-hover">
            <div class="card-body d-flex align-items">
                <div class="text-center px-4">
                    <img src="{{ Str::remove('-dark',$brand->image) }}" class="theme-light-show mw-100 mh-30px my-2" alt="{{$brand->name}}" />
                        <img src="{{ $brand->image }}" class="theme-dark-show mw-100 mh-30px my-2" alt="{{$brand->name}}" />
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>