<?php
use Carbon\Carbon;
use App\Models\Promo;
use Livewire\Volt\Component;

new class extends Component {
    public $promos;
    public function mount()
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);

        $this->promos = Promo::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where(function ($query) use ($isWeekend) {
                $query->where('day_restriction', 'all')
                    ->orWhere('day_restriction', $isWeekend ? 'weekend' : 'weekday');
            })
            ->orderBy('end_date')
            ->get();
    }
};
?>
<div>
    <div class="promo-header">
        <h2>Promo spesial hari ini</h2>
        <p class="mb-0">Banyak promo yang pas buat kamu!</p>
        <img src="{{ asset('assets/media/bg/header-promo.png') }}" alt="Promo Icon">
    </div>
    <div class="py-6 py-lg-10 bg-light">
        <div class="container">
            <!-- Header Section -->

            <!-- Tab Navigation -->
            <div class="mb-6">
                <ul class="nav nav-pills nav-fill bg-white shadow-sm rounded-pill p-1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active fw-bold text-gray-700 rounded-pill px-4 py-2" data-bs-toggle="tab"
                            href="#tab_promo">
                            <i class="ki-outline ki-gift fs-5 me-2"></i> Promo <span
                                class="badge bg-primary rounded-pill">{{ $this->promos->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link fw-bold text-gray-700 rounded-pill px-4 py-2" data-bs-toggle="tab"
                            href="#tab_coupon">
                            <i class="ki-outline ki-ticket fs-5 me-2"></i> Kupon
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Promo Content -->
            <div class="tab-content" id="promoTabContent">
                <div class="tab-pane fade show active" id="tab_promo" role="tabpanel">
                    @if($this->promos->count() > 0)
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            @foreach($this->promos as $promo)
                                <div class="col">
                                    <div
                                        class="card h-100 shadow-sm rounded-4 overflow-hidden hover-elevate-up transition-all duration-300">
                                        <div class="position-relative">
                                            <div class="theme-light-show">
                                                <img src="{{ file_exists(public_path('storage/promo/' . $promo->code . '.png')) ? asset('storage/promo/' . $promo->code . '.png') : asset('assets/media/illustrations/icons/tickets.png') }}"
                                                    alt="{{ $promo->name }}" class="card-img-top"
                                                    style="height: 200px; object-fit: cover;">
                                            </div>
                                            <div class="theme-dark-show">
                                                <img src="{{ file_exists(public_path('storage/promo/' . $promo->code . '.png')) ? asset('storage/promo/' . $promo->code . '-dark.png') : asset('assets/media/illustrations/icons/tickets-dark.png') }}"
                                                    alt="{{ $promo->name }}" class="card-img-top"
                                                    style="height: 200px; object-fit: cover;">
                                            </div>
                                            @if($promo->is_featured)
                                                <div
                                                    class="position-absolute top-0 end-0 bg-danger text-white fs-6 fw-bold px-3 py-1 rounded-bottom-left">
                                                    HOT DEAL!
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body p-4">
                                            <h3 class="fw-bold fs-2 mb-2">{{ $promo->name }}</h3>
                                            @if($promo->description)
                                                <p class="text-muted mb-3">{!! Str::limit($promo->description, 100) !!}</p>
                                            @endif
                                            @if($promo->code)
                                                <div class="mb-3">
                                                    <span
                                                        class="badge bg-warning text-dark fs-6 fw-bold px-3 py-2 rounded-pill">
                                                        {{ $promo->code }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Berlaku hingga
                                                    {{ Carbon::parse($promo->end_date)->translatedFormat('d F Y') }}</span>
                                                <a href="#products" data-kt-scroll-toggle
                                                    class="btn btn-sm btn-primary fw-bold">
                                                    <i class="ki-outline ki-basket fs-5 me-1"></i> Lihat Produk
                                                </a>
                                            </div>
                                            <!-- Countdown -->
                                            <div class="mt-3">
                                                <div class="bg-light p-2 rounded-3">
                                                    <div class="d-flex justify-content-around text-center">
                                                        <div>
                                                            <div class="bg-white text-primary fw-bold fs-4 px-2 py-1 rounded-2 countdown-box"
                                                                id="days-{{ $promo->id }}">00</div>
                                                            <small class="text-muted d-block mt-1">Hari</small>
                                                        </div>
                                                        <div>
                                                            <div class="bg-white text-primary fw-bold fs-4 px-2 py-1 rounded-2 countdown-box"
                                                                id="hours-{{ $promo->id }}">00</div>
                                                            <small class="text-muted d-block mt-1">Jam</small>
                                                        </div>
                                                        <div>
                                                            <div class="bg-white text-primary fw-bold fs-4 px-2 py-1 rounded-2 countdown-box"
                                                                id="minutes-{{ $promo->id }}">00</div>
                                                            <small class="text-muted d-block mt-1">Menit</small>
                                                        </div>
                                                        <div>
                                                            <div class="bg-white text-primary fw-bold fs-4 px-2 py-1 rounded-2 countdown-box"
                                                                id="seconds-{{ $promo->id }}">00</div>
                                                            <small class="text-muted d-block mt-1">Detik</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here.png') }}"
                                class="w-200px mb-5 theme-light-show" alt="No Promotions">
                            <img src="{{ asset('assets/media/illustrations/there-is-nothing-here-dark.png') }}"
                                class="w-200px mb-5 theme-dark-show" alt="No Promotions">
                            <h3 class="fw-bold fs-3 text-gray-800 mb-3">Tidak Ada Promo Saat Ini</h3>
                            <p class="text-muted fs-5 mb-4">Pantau terus untuk promo terbaru!</p>
                            <a href="{{ route('home') }}" wire:navigate
                                class="btn btn-primary rounded-pill px-5 py-2 fw-bold">
                                <i class="ki-outline ki-arrow-left fs-5 me-2"></i> Kembali ke Beranda
                            </a>
                        </div>
                    @endif
                </div>
                <div class="tab-pane fade" id="tab_coupon" role="tabpanel">
                    <!-- Coupon Tab Content -->
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <div class="coupon-card shadow-sm">
                                <div class="coupon-header">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/DBS_Bank_logo.svg/512px-DBS_Bank_logo.svg.png"
                                        alt="DBS Logo">
                                    <div>
                                        <h6 class="mb-0">Bank DBS</h6>
                                        <small class="text-muted">digibank by DBS</small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p class="text-warning fw-bold mb-1">Diskon</p>
                                    <p class="coupon-discount mb-1">s.d. Rp600rb</p>
                                    <small class="text-muted">Min. Transaksi Rp2juta</small>
                                </div>
                                <div class="coupon-footer">
                                    <span class="coupon-code">DBSDAILY</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="coupon-card shadow-sm">
                                <div class="coupon-header">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/DBS_Bank_logo.svg/512px-DBS_Bank_logo.svg.png"
                                        alt="DBS Logo">
                                    <div>
                                        <h6 class="mb-0">Bank DBS</h6>
                                        <small class="text-muted">digibank by DBS</small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p class="text-warning fw-bold mb-1">Diskon</p>
                                    <p class="coupon-discount mb-1">s.d. Rp600rb</p>
                                    <small class="text-muted">Min. Transaksi Rp2juta</small>
                                </div>
                                <div class="coupon-footer">
                                    <span class="coupon-code">DBSDAILY</span>
                                    <button class="btn btn-success btn-sm" onclick="copyCode()">Copy</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="#" class="btn btn-outline-success fw-bold">Lihat Semua</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ✅ Kode kupon berhasil disalin!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    </div>
    @foreach($this->promos as $promo)
        @section('custom_js')
            <script data-navigate-once>
                document.addEventListener('DOMContentLoaded', function () {
                    function copyCode() {
                        const code = document.getElementById("couponCode").innerText;
                        navigator.clipboard.writeText(code).then(() => {
                            const toastEl = document.getElementById("copyToast");
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        });
                    }
                    function updateCountdown{{ $promo->id }}() {
                        const daysElement = document.getElementById('days-{{ $promo->id }}');
                        const hoursElement = document.getElementById('hours-{{ $promo->id }}');
                        const minutesElement = document.getElementById('minutes-{{ $promo->id }}');
                        const secondsElement = document.getElementById('seconds-{{ $promo->id }}');

                        if (!daysElement || !hoursElement || !minutesElement || !secondsElement) {
                            if (typeof countdownTimer{{ $promo->id }} !== 'undefined') {
                                clearInterval(countdownTimer{{ $promo->id }});
                            }
                            return;
                        }

                        const endDate = new Date('{{ $promo->end_date }}');
                        const now = new Date();
                        const distance = endDate - now;

                        if (distance < 0) {
                            const card = daysElement.closest('.card');
                            if (card) card.remove();
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

                    updateCountdown{{ $promo->id }}();
                    const countdownTimer{{ $promo->id }} = setInterval(updateCountdown{{ $promo->id }}, 1000);
                });

                document.addEventListener('livewire:navigated', function () {
                    function updateCountdown{{ $promo->id }}() {
                        const daysElement = document.getElementById('days-{{ $promo->id }}');
                        const hoursElement = document.getElementById('hours-{{ $promo->id }}');
                        const minutesElement = document.getElementById('minutes-{{ $promo->id }}');
                        const secondsElement = document.getElementById('seconds-{{ $promo->id }}');

                        if (!daysElement || !hoursElement || !minutesElement || !secondsElement) {
                            if (typeof countdownTimer{{ $promo->id }} !== 'undefined') {
                                clearInterval(countdownTimer{{ $promo->id }});
                            }
                            return;
                        }

                        const endDate = new Date('{{ $promo->end_date }}');
                        const now = new Date();
                        const distance = endDate - now;

                        if (distance < 0) {
                            const card = daysElement.closest('.card');
                            if (card) card.remove();
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

                    updateCountdown{{ $promo->id }}();
                    const countdownTimer{{ $promo->id }} = setInterval(updateCountdown{{ $promo->id }}, 1000);
                });
            </script>
        @endsection
    @endforeach
</div>