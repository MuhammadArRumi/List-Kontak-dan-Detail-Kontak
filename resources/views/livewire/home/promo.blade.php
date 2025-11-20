<?php
use Carbon\Carbon;
use App\Models\Promo;
use Livewire\Volt\Component;
// use function Livewire\Volt\{computed,placeholder};
// placeholder(view('livewire.skeleton.promo-home'));


new class extends Component
{
    public $promo;
    
    public function mount()
    {
        // Hapus sleep(3) dari sini
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);
        $this->promo = Promo::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where(function($query) use ($isWeekend) {
                $query->where('day_restriction', 'all')
                    ->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
            })
            ->orderBy('end_date')
            ->get();
    }
    public function placeholder()
    {
        return view('livewire.skeleton.promo-home');
    }
};
?>
@auth
<div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
    @if($this->promo->count() > 0)
        <div class="carousel-inner rounded-4 overflow-hidden shadow-lg">
            @foreach($this->promo as $index => $coupon)
            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                <div class="py-8 py-lg-12 bg-gradient-promo">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-6 mb-lg-0 text-white">
                                <div class="badge promo-badge text-white fs-6 fw-bold mb-3 py-2 px-3 rounded-pill">
                                    PROMO KHUSUS
                                </div>
                                <h1 class="display-5 fw-bold mb-4">{{ $coupon->name }}</h1>
                                
                                @if($coupon->description)
                                    <p class="fs-5 mb-4 opacity-75">{!! Str::limit($coupon->description, 150) !!}</p>
                                @endif
                                
                                @if($coupon->code)
                                <div class="d-flex align-items-center mb-5">
                                    <span class="fs-5 me-2">Gunakan kode:</span>
                                    <span class="badge bg-warning text-dark fs-4 fw-bold px-4 py-2 rounded-pill">
                                        {{ $coupon->code }}
                                    </span>
                                </div>
                                @endif
                                
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <a href="#products" data-kt-scroll-toggle class="btn btn-light btn-lg fw-bold rounded-pill px-5 py-3 shadow-sm">
                                        <i class="ki-outline ki-basket fs-2 me-2"></i> Lihat Produk
                                    </a>
                                    <div class="d-flex align-items-center fs-5 text-white-50">
                                        <i class="ki-outline ki-clock fs-2 me-2"></i>
                                        <span>Berlaku hingga {{ Carbon::parse($coupon->end_date)->translatedFormat('d F Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 position-relative">
                                <div class="position-relative">
                                    <div class="theme-light-show text-center">
                                        <img src="{{ file_exists(public_path('storage/promo/' . $coupon->code.'.png')) ? asset('storage/promo/' . $coupon->code.'.png') : asset('assets/media/illustrations/icons/tickets.png') }}" alt="Promo Banner" 
                                            class="img-fluid w-75 rounded-4 animate-float">
                                    </div>
                                    <div class="theme-dark-show text-center">
                                        <img src="{{ file_exists(public_path('storage/promo/' . $coupon->code.'.png')) ? asset('storage/promo/' . $coupon->code.'-dark.png') : asset('assets/media/illustrations/icons/tickets-dark.png') }}" alt="Promo Banner" 
                                            class="img-fluid w-75 rounded-4 animate-float">
                                    </div>
                                    @if($coupon->is_featured)
                                    <div class="position-absolute top-0 end-0 bg-danger fs-3 fw-bold px-4 py-2 rounded-3 shadow" 
                                        style="transform: rotate(15deg);">
                                        HOT DEAL!
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Promo Countdown -->
                        <div class="mt-8">
                            <div class="bg-white bg-opacity-20 p-4 rounded-4">
                                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                                    <div class="mb-3 mb-md-0">
                                        <h3 class="fw-bold fs-3 mb-0 text-white">Promo Berakhir Dalam:</h3>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div class="text-center">
                                            <div class="bg-white text-primary fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" 
                                                id="days-{{ $coupon->id }}">00</div>
                                            <span class="text-white-50 mt-1 d-block fs-6">Hari</span>
                                        </div>
                                        <div class="text-center">
                                            <div class="bg-white text-primary fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" 
                                                id="hours-{{ $coupon->id }}">00</div>
                                            <span class="text-white-50 mt-1 d-block fs-6">Jam</span>
                                        </div>
                                        <div class="text-center">
                                            <div class="bg-white text-primary fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" 
                                                id="minutes-{{ $coupon->id }}">00</div>
                                            <span class="text-white-50 mt-1 d-block fs-6">Menit</span>
                                        </div>
                                        <div class="text-center">
                                            <div class="bg-white text-primary fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" 
                                                id="seconds-{{ $coupon->id }}">00</div>
                                            <span class="text-white-50 mt-1 d-block fs-6">Detik</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        @if($this->promo->count() > 1)
        <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        @endif
        @foreach($this->promo as $coupon)
            @section('custom_js')
            <script data-navigate-once>
                document.addEventListener('DOMContentLoaded', function() {
                    function updateCountdown{{ $coupon->id }}() {
                        const daysElement = document.getElementById('days-{{ $coupon->id }}');
                        const hoursElement = document.getElementById('hours-{{ $coupon->id }}');
                        const minutesElement = document.getElementById('minutes-{{ $coupon->id }}');
                        const secondsElement = document.getElementById('seconds-{{ $coupon->id }}');
                        
                        // If any element is missing, stop the countdown
                        if (!daysElement || !hoursElement || !minutesElement || !secondsElement) {
                            if (typeof countdownTimer{{ $coupon->id }} !== 'undefined') {
                                clearInterval(countdownTimer{{ $coupon->id }});
                            }
                            return;
                        }
                        
                        const endDate = new Date('{{ $coupon->end_date }}');
                        const now = new Date();
                        const distance = endDate - now;
                        
                        if (distance < 0) {
                            document.querySelectorAll(`[data-promo="{{ $coupon->id }}"]`).forEach(el => {
                                el.remove();
                            });
                            return;
                        }
                        
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        daysElement.textContent = days.toString().padStart(2, '0');
                        hoursElement.textContent = hours.toString().padStart(2, '0');
                        minutesElement.textContent = minutes.toString().padStart(2, '0');
                        secondsElement.textContent = seconds.toString().padStart(2, '0');
                    }
                    
                    updateCountdown{{ $coupon->id }}();
                    const countdownTimer{{ $coupon->id }} = setInterval(updateCountdown{{ $coupon->id }}, 1000);
                });
                
                document.addEventListener('livewire:navigated', function() {
                    function updateCountdown{{ $coupon->id }}() {
                        const daysElement = document.getElementById('days-{{ $coupon->id }}');
                        const hoursElement = document.getElementById('hours-{{ $coupon->id }}');
                        const minutesElement = document.getElementById('minutes-{{ $coupon->id }}');
                        const secondsElement = document.getElementById('seconds-{{ $coupon->id }}');
                        
                        // If any element is missing, stop the countdown
                        if (!daysElement || !hoursElement || !minutesElement || !secondsElement) {
                            if (typeof countdownTimer{{ $coupon->id }} !== 'undefined') {
                                clearInterval(countdownTimer{{ $coupon->id }});
                            }
                            return;
                        }
                        
                        const endDate = new Date('{{ $coupon->end_date }}');
                        const now = new Date();
                        const distance = endDate - now;
                        
                        if (distance < 0) {
                            document.querySelectorAll(`[data-promo="{{ $coupon->id }}"]`).forEach(el => {
                                el.remove();
                            });
                            return;
                        }
                        
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        daysElement.textContent = days.toString().padStart(2, '0');
                        hoursElement.textContent = hours.toString().padStart(2, '0');
                        minutesElement.textContent = minutes.toString().padStart(2, '0');
                        secondsElement.textContent = seconds.toString().padStart(2, '0');
                    }
                    
                    updateCountdown{{ $coupon->id }}();
                    const countdownTimer{{ $coupon->id }} = setInterval(updateCountdown{{ $coupon->id }}, 1000);
                });
            </script>
            @endsection
        @endforeach
    @else
    <div class="py-8 py-lg-12 bg-gradient-promo rounded-4 shadow-lg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-6 mb-lg-0 text-white">
                    <div class="badge promo-badge text-white fs-6 fw-bold mb-3 py-2 px-3 rounded-pill">
                        PROMO MENDATANG
                    </div>
                    <h1 class="display-5 fw-bold mb-4">Nikmati Promo Spesial Kami</h1>
                    <p class="fs-5 mb-4 opacity-75">Pantau terus halaman ini untuk mendapatkan informasi promo terbaru dari kami. Kami sering memberikan diskon spesial di akhir pekan dan hari libur.</p>
                    
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <a href="#products" data-kt-scroll-toggle class="btn btn-light btn-lg fw-bold rounded-pill px-5 py-3 shadow-sm">
                            <i class="ki-outline ki-basket fs-2 me-2"></i> Lihat Produk
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 position-relative">
                    <div class="position-relative">
                        <div class="theme-light-show text-center">
                            <img src="{{ asset('assets/media/illustrations/icons/tickets.png') }}" alt="Promo Banner" 
                                class="img-fluid w-75 rounded-4 animate-float">
                        </div>
                        <div class="theme-dark-show text-center">
                            <img src="{{ asset('assets/media/illustrations/icons/tickets-dark.png') }}" alt="Promo Banner" 
                                class="img-fluid w-75 rounded-4 animate-float">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@else
<div class="hero-section py-10 py-lg-15 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-8 mb-lg-0">
                <h1 class="display-5 fw-bold mb-4">Sewa & Beli Elektronik dengan Mudah</h1>
                <p class="fs-5 text-muted mb-6">Temukan berbagai elektronik berkualitas untuk kebutuhan Anda dengan harga terjangkau.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" wire:navigate class="btn btn-primary btn-lg fw-bold px-5 py-3 rounded-pill shadow-sm">
                        Daftar Sekarang
                    </a>
                    <a href="{{ route('login') }}" wire:navigate class="btn btn-outline-primary btn-lg px-5 py-3 rounded-pill">
                        Masuk
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('assets/media/illustrations/goods.png') }}" alt="Hero Illustration" 
                    class="img-fluid rounded-4 shadow-lg animate-float theme-light-show" style="max-width: 85%;" loading="lazy">
                <img src="{{ asset('assets/media/illustrations/goods-dark.png') }}" alt="Hero Illustration" 
                    class="img-fluid rounded-4 shadow-lg animate-float theme-dark-show" style="max-width: 85%;" loading="lazy">
            </div>
        </div>
    </div>
</div>
@endauth