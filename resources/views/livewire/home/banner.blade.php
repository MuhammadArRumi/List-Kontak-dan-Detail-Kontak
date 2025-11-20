<?php
use App\Models\Banner;
use Livewire\Volt\Component;

new class extends Component
{
    public $banner;
    
    public function mount()
    {
        $this->banner = Banner::where('st', true)->get();
    }
};
?>
<style>
    
    /* Style untuk gambar responsif */
    .responsive-img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        max-height: 400px;
        object-fit: cover;
    }
    /* Responsivitas untuk perangkat mobile */
    @media (max-width: 768px) {
        .responsive-img {
            max-height: 300px;
        }
    }
    @media (max-width: 480px) {
        .responsive-img {
            max-height: 250px;
        }
    }
</style>
<div class="tns">
    <div class="tns-slider">
        @forelse($this->banner as $index => $item)
        <!--begin::Item-->
        <div class="slide-item" @if($index != 0) style="display: none;" @endif>
            <!-- Simulasi loop dengan beberapa gambar -->
            <img src="{{asset( 'storage/'. $item->thumbnail) }}" class="responsive-img" alt="Pemandangan gunung" />
        </div>
        <!--end::Item-->
        @empty
        <div class="slide-item">
            <!-- Simulasi loop dengan beberapa gambar -->
            <img src="https://via.placeholder.com/1200x400/eeeeee/999999?text=Tidak+Ada+Banner+Tersedia" class="responsive-img" alt="" />
        </div>
        @endforelse
    </div>
        <!-- <button class="nav-btn" id="banner_prev">
            <i>←</i>
        </button>
        <button class="nav-btn" id="banner_next">
            <i>→</i>
        </button>
        
        <div class="slider-indicators">
            <div class="indicator active"></div>
        </div>  -->
</div>