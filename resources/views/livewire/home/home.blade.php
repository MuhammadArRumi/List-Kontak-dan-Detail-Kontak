<?php
use Carbon\Carbon;
use App\Models\Promo;
use App\Models\Master\Brand;
use Livewire\Volt\Component;
use App\Models\Master\Category;
use function Laravel\Folio\name;

name('home');
new class extends Component
{
    public $promo;
    public $brands;
    public $branch = '';
    public $search = '';
    public $selectedColor = '';
    public $selectedStorage = '';
    public $selectedProduct = '';
    
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
        $this->brands = Brand::where('st', 'a')
        ->withCount('products')
        ->orderBy('name')
        ->limit(6)->get();
        $this->categories = Category::where('st', 'a')
        ->withCount('products')
        ->orderBy('name')
        ->get();

        $this->dpos = User::onlyBanned()->get();
    }
    #[Computed]
    public function branches()
    {
        return Branch::where('st', 'a')->orderBy('name')->pluck('name', 'id')->prepend('Pilih Cabang', '');
    }
    #[Computed]
    public function promos()
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);

        return Promo::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where(function ($query) use ($isWeekend) {
                $query->where('day_restriction', 'all')->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
            })
            ->first();
    }
    #[Computed]
    public function products()
    {
        if (!$this->branch) {
            return ProductBranch::with(['product.category', 'color', 'storage', 'branch'])
                ->where('is_publish', 1)
                ->when($this->search, function ($query) {
                    $query->whereHas('product', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')->orWhere('description_rent', 'like', '%' . $this->search . '%');
                    });
                })
                ->limit(50)
                ->get()
                ->groupBy('product_id');
        }
        return ProductBranch::with(['product.category', 'color', 'storage', 'branch'])
            ->where('branch_id', $this->branch)
            ->where('is_publish', 1)
            ->when($this->search, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('description_rent', 'like', '%' . $this->search . '%');
                });
            })
            ->get()
            ->groupBy('product_id');
    }
    public function set($type, $value)
    {
        $this->$type = $value;
        $this->dispatch('variant-selected');
    }
};
?>
<x-default-layout>
    @volt
    <div>
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
        @if(Auth::check() && Auth::user()->getRoleNames()[0] == 'Onboarding')
            <div class="mt-10 mb-10">
                <div class="card border-0 shadow-sm rounded-4 bg-light-primary overflow-hidden">
                    <div class="card-body p-6 p-md-8">
                        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
                            <div class="d-flex align-items-center gap-4">
                                <i class="ki-solid ki-shield-tick fs-2hx text-primary"></i>
                                <div>
                                    <h3 class="fw-bold text-gray-900 fs-3 mb-2">
                                        @if(Auth::check()) Verifikasi Akun Anda @else Daftar dan Verifikasi Sekarang @endif
                                    </h3>
                                    <p class="text-gray-600 fs-5">
                                        @if(Auth::check())
                                            Lengkapi data diri untuk akses penuh ke semua fitur dan promo eksklusif!
                                        @else
                                            Daftar dan verifikasi akun Anda untuk menikmati sewa dan beli elektronik dengan mudah.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('profile.verification') }}" wire:navigate
                            class="btn btn-primary rounded-pill px-6 py-3 fw-bold d-flex align-items-center gap-2"
                            aria-label="Verifikasi akun">
                                <i class="ki-outline ki-arrow-right fs-2"></i>
                                @if(Auth::check()) Verifikasi Sekarang @else Mulai Sekarang @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if(Auth::check() && Auth::user()->getRoleNames()[0] == 'Konsumen' && Auth::user()->st == "verified")
            <div class="card mb-10 mt-10">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6">
                                <div class="position-relative pb-2 mb-4 mb-md-0">
                                    <h2 class="fw-bold text-gray-900 mb-0 fs-2">Brand Pilihan</h2>
                                    <div class="position-absolute bottom-0 start-0 w-50 h-2px bg-info rounded"></div>
                                </div>
                            </div>
                            <div class="row home-brand">
                                @foreach ($this->brands as $brand)
                                <div class="col-3 col-md-3 col-lg-2">
                                    <a href="#" class="card hover-elevate-up shadow-sm parent-hover">
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
                        </div>
                        <div class="d-none d-md-block col-md-6">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6">
                                <div class="position-relative pb-2 mb-4 mb-md-0">
                                    <h2 class="fw-bold text-gray-900 mb-0 fs-2">Top Up & Tarik Saldo</h2>
                                    <div class="position-absolute bottom-0 start-0 w-50 h-2px bg-success rounded"></div>
                                </div>
                            </div>
                            <div class="mb-5 hover-scroll-x">
                                <div class="d-grid">
                                    <ul class="nav nav-tabs flex-nowrap text-nowrap">
                                        <li class="nav-item">
                                            <a class="nav-link active btn btn-active-light btn-color-gray-600 btn-active-color-primary rounded-bottom-0" data-bs-toggle="tab" href="#tab_topup">Top Up</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-active-light btn-color-gray-600 btn-active-color-primary rounded-bottom-0" data-bs-toggle="tab" href="#tab_withdraw">Tarik Saldo</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="tab_topup" role="tabpanel">
                                    <form wire:submit.prevent="topUp">
                                        <label class="form-label">Jumlah Top Up</label>
                                        <div class="d-flex gap-2">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" placeholder="Masukkan jumlah" min="50000" step="50000">
                                                </div>
                                            </div>
                                            <button class="btn btn-success flex-shrink-0">Top Up</button>
                                        </div>
                                        <div class="form-text mt-2">Minimal top up: Rp 50.000</div>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="tab_withdraw" role="tabpanel">
                                    <form wire:submit.prevent="withdraw">
                                        <div class="row">
                                            <div class="col-4 form-group">
                                                <label class="form-label">Jumlah Penarikan</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" placeholder="Masukkan jumlah" min="50000" step="50000">
                                                </div>
                                            </div>
                                            <div class="col-4 form-group">
                                                <label class="form-label">Rekening Tujuan</label>
                                                <select class="form-select">
                                                    <option>BCA - *** 1234 (Saldo Utama)</option>
                                                    <option>BRI - *** 5678</option>
                                                    <option>Mandiri - *** 9012</option>
                                                    <option>BNI - *** 3456</option>
                                                </select>
                                            </div>
                                            <div class="col-4 form-group">
                                                <button class="btn btn-success mt-9">Tarik Saldo</button>
                                            </div>
                                        </div>
                                        <div class="form-text mt-2">Minimal Penarikan: Rp 100.000</div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="home-category mt-10 mb-10" aria-label="dynamic icon wrapper" role="region">
                                @foreach($this->categories as $category)
                                <a wire:navigate href="{{ route('category.show', ['category' => $category]) }}" class="home-item-category" tabindex="0" role="button" data-testid="icnHomeDynamicIcon#2">
                                    <img src="{{ $category->image ?? asset('assets/media/placeholder/category.png') }}" alt="{{ $category->name }}">
                                    <span>
                                        {{ $category->name }}
                                    </span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4 mb-6">
                <div class="position-relative pb-2 mb-4 mb-md-0">
                    <h2 class="fw-bold text-gray-900 mb-0 fs-2">Untuk Kamu</h2>
                    <div class="position-absolute bottom-0 start-0 w-50 h-2px bg-primary rounded"></div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-3 w-100 w-md-auto">
                    <div class="position-relative w-100" style="max-width: 420px; z-index: 1;">
                        <div class="input-group input-group-lg shadow">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="ki-filled ki-magnifier text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0" placeholder="Search shop" value="">
                            <span class="input-group-text bg-transparent border-0">
                                <span class="badge bg-outline-secondary border border-secondary ms-1">⌘ K</span>
                            </span>
                        </div>
                    </div>
                    <x-form-select name="branch" modifier="live" class="form-select form-select-lg rounded-pill shadow-sm"
                        :options="$this->branches" aria-label="Pilih cabang" />
                </div>
            </div>
            @if ($this->branch != "")
                @php
                    $cabang = Branch::find($this->branch);
                @endphp
                <div class="mb-8 d-none">
                    <div class="alert alert-primary d-flex align-items-center p-4 rounded-4 shadow-sm">
                        <i class="ki-outline ki-shop fs-1 me-4"></i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1">Cabang {{ $cabang->name }}</h4>
                            <span>Alamat: {{ $cabang->address }}</span>
                            <small class="text-muted">Jam Operasional:
                                {{ $cabang->operational_hours ?? '09:00 - 21:00' }}</small>
                        </div>
                    </div>
                </div>
                @if ($this->products->count() > 0)
                    <div class="row row-cols-2 row-cols-sm-2 row-cols-xl-6 g-3 mb-2">
                        @foreach ($this->products as $productId => $variations)
                            @php
                            $firstVariant = $variations->first();
                            $product = $firstVariant->product;
                            $totalStock = $variations->sum('stock');
                            // Get available variants based on selections
                            $availableVariants = $variations;
                            if ($this->selectedColor) {
                                $availableVariants = $availableVariants->where('color_id', $this->selectedColor);
                            }
                            if ($this->selectedStorage) {
                                $availableVariants = $availableVariants->where('storage_id', $this->selectedStorage);
                            }
                            $selectedVariant = $availableVariants->first() ?? $firstVariant;
                            // Get unique colors and storages
                            $uniqueColors = $variations->unique('color_id')->filter(fn($v) => $v->color);
                            $uniqueStorages = $variations->unique('storage_id')->filter(fn($v) => $v->storage);
                            // Determine product image based on selected color
                            $colorImage = null;
                            if ($selectedVariant->color) {
                                $colorImage = asset(
                                    'storage/product/' .
                                        $product->slug .
                                        '-' .
                                        Str::slug($selectedVariant->color->value) .
                                        '.png',
                                );
                            }
                            $cabang = Branch::where('id', $this->branch)->first();
                            // Determine route based on auth status
                            $routeProduct = route('product.show', [
                                'product' => $product->slug,
                                'branch' => $cabang->slug,
                            ]);
                            // Promo calculations
                            $promo = $this->promos;
                            $showPromo =
                                $promo &&
                                ($promo->scope === 'all' ||
                                    ($promo->scope === 'products' && $promo->products->contains($product->id))) &&
                                ($selectedVariant->sale_price >= ($promo->min_order_amount ?? 0) ||
                                    $selectedVariant->rent_price >= ($promo->min_order_amount ?? 0)) &&
                                ($promo->max_uses === null || $promo->max_uses > $promo->usages->count()) &&
                                in_array($promo->type, ['percentage', 'fixed_amount']);

                            // Calculate discounted prices
                            $discountedSale = $selectedVariant->sale_price;
                            $discountedRent = $selectedVariant->rent_price;
                            if ($showPromo) {
                                if ($promo->type === 'percentage') {
                                    $discountedSale = $selectedVariant->sale_price * (1 - $promo->value / 100);
                                    $discountedRent = $selectedVariant->rent_price * (1 - $promo->value / 100);
                                } elseif ($promo->type === 'fixed_amount') {
                                    $discountedSale = max(0, $selectedVariant->sale_price - $promo->value);
                                    $discountedRent = max(0, $selectedVariant->rent_price - $promo->value);
                                }
                            }
                        @endphp
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column justify-content-between p-2">
                                    <div class="mb-3">
                                        <div class="card bg-light mb-3 d-flex align-items-center justify-content-center position-relative"
                                            style="height: 180px;">
                                            <img alt="" class="img-fluid" style="height: 180px;" data-bs-toggle="drawer"
                                                src="{{ $colorImage ?? $product->image }}" loading="lazy" wire:loading.class.delay="opacity-50">
                                            <div wire:loading.delay class="position-absolute top-50 start-50 translate-middle">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <a class="text-decoration-none hover:text-primary text-sm fw-medium text-mono d-block px-2"
                                            data-bs-toggle="drawer" href="#drawers_shop_product_details">
                                            {{ $product->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm fw-medium text-mono px-2 mb-3">
                                        <label class="form-label fw-semibold d-block mb-2">Warna</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($uniqueColors as $variant)
                                                @if ($variant->color)
                                                    @php
                                                        $isActive = $this->selectedColor == $variant->color_id;
                                                        $isDisabled =
                                                            $this->selectedStorage &&
                                                            !$variations->contains(
                                                                fn($v) => $v->color_id == $variant->color_id &&
                                                                    $v->storage_id == $this->selectedStorage,
                                                            );
                                                    @endphp
                                                    <div data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="{{ $variant->color->value }}"
                                                        class="color-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                                        style="background-color: {{ $variant->warna($variant->color->value) }};"
                                                        wire:click="set('selectedColor', {{ $variant->color_id }})"
                                                        aria-label="Pilih warna {{ $variant->color->value }}"
                                                        @if ($isDisabled) aria-disabled="true" @endif>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <!-- Storage Options -->
                                    <div class="text-sm fw-medium text-mono px-2 mb-3">
                                        <label class="form-label fw-semibold d-block mb-2">Storage</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($uniqueStorages as $variant)
                                                @php
                                                    $isActive = $this->selectedStorage == $variant->storage_id;
                                                    $isDisabled =
                                                        $this->selectedColor &&
                                                        !$variations->contains(
                                                            fn($v) => $v->storage_id == $variant->storage_id &&
                                                                $v->color_id == $this->selectedColor,
                                                        );
                                                @endphp
                                                <span
                                                    class="badge bg-light text-dark py-2 px-3 storage-option {{ $isActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                                    wire:click="set('selectedStorage', {{ $variant->storage_id }})"
                                                    aria-label="Pilih storage {{ $variant->storage->value }}"
                                                    @if ($isDisabled) aria-disabled="true" @endif>
                                                    {{ $variant->storage->value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="text-sm fw-medium text-mono">
                                                Rp{{ number_format($selectedVariant->rent_price, 0, ',', '.') }} /hari
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            {{-- <button class="btn btn-outline-dark btn-sm ms-1" data-bs-toggle="drawer"
                                                href="#drawers_shop_cart">
                                                <i class="ki-filled ki-handcart"></i> Add
                                            </button> --}}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="badge bg-warning rounded-pill">
                                                <i class="ki-solid ki-star text-white me-1"></i>
                                                {{ number_format($product->averageRating()) }}
                                            </div>
                                            @if ($product->ratings->count() > 0)
                                            <span class="text-sm fw-medium text-mono">
                                                ({{ $product->ratings->count() }})
                                            </span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            {{ $selectedVariant->branch->name }}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center px-2">
                                        <div class="d-flex align-items-center gap-1">
                                            {{-- <button class="btn btn-outline-dark btn-sm ms-1" data-bs-toggle="drawer"
                                                href="#drawers_shop_cart">
                                                <i class="ki-filled ki-handcart"></i> Tambah
                                            </button> --}}
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ $routeProduct }}" wire:navigate class="btn btn-outline-dark btn-sm ms-1">
                                                <i class="ki-filled ki-handcart"></i> Sewa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-15">
                        <img src="{{ asset('assets/media/illustrations/there-is-nothing-here.png') }}"
                            class="w-200px mb-5 theme-light-show" alt="No Products" loading="lazy">
                        <img src="{{ asset('assets/media/illustrations/there-is-nothing-here-dark.png') }}"
                            class="w-200px mb-5 theme-dark-show" alt="No Products" loading="lazy">
                        <h3 class="text-gray-600">Produk tidak ditemukan</h3>
                        <p class="text-muted">Silakan cari dengan kata kunci lain atau pilih cabang berbeda</p>
                        <button class="btn btn-primary rounded-pill px-6" wire:click="$set('search', '')">
                            Reset Pencarian
                        </button>
                    </div>
                @endif
            @else
                <div class="text-center py-15">
                    <img src="{{ asset('assets/media/illustrations/shop.png') }}" 
                        class="w-200px mb-5 theme-light-show" 
                        alt="Select Branch"
                        loading="lazy">
                    <img src="{{ asset('assets/media/illustrations/shop-dark.png') }}" 
                        class="w-200px mb-5 theme-dark-show" 
                        alt="Select Branch"
                        loading="lazy">
                    <h3 class="text-gray-600">Silakan pilih cabang terlebih dahulu</h3>
                    <p class="text-muted">Pilih cabang untuk melihat produk yang tersedia</p>
                </div>
            @endif
        @endif
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
    </div>
    @endvolt
</x-default-layout>