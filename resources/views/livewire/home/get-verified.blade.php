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