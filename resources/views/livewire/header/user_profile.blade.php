<?php
use function Livewire\Volt\{state};
use Illuminate\Support\Facades\Auth;

state([
    'ongoingTransactionCounts' => fn() => Auth::check() ? Auth::user()->ongoingTransaction() : 0
]);
$headerloadTransaction = function() {
    if (Auth::check()) {
        $this->ongoingTransactionCounts = Auth::user()->ongoingTransaction();
    }
};
$logout = function(){
    auth()->logout();
    $route = route('home');
    return $this->redirect($route, navigate: true);
};
?>
<!--begin::User account menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <div class="menu-content d-flex align-items-center px-3">
            <!--begin::Avatar-->
            <div class="symbol symbol-50px me-5">
                @if(Auth::user()->profile_photo_url)
                    <img alt="Logo" src="{{ Auth::user()->profile_photo_url }}"/>
                @else
                    <div class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', Auth::user()->name) }}">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                @endif
            </div>
            <!--end::Avatar-->
            <!--begin::Username-->
            <div class="d-flex flex-column">
                <div class="fw-bold d-flex align-items-center fs-5">{{ Auth::user()->name}}
                    <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">
                        {{ auth()->user()->getRoleNames()->first() }}
                    </span>
                </div>
                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                    {{ \Illuminate\Support\Str::limit(auth()->user()->email, 23, $end='...') }}
                </a>
            </div>
            <!--end::Username-->
        </div>
    </div>
    <!--end::Menu item-->
    <!--begin::Menu separator-->
    <div class="separator my-2"></div>
    <!--end::Menu separator-->
    <!--begin::Menu item-->
    <div class="menu-item px-5">
        <a wire:navigate href="{{ route('profile.edit') }}" wire:navigate class="menu-link px-5">Profil Saya</a>
    </div>
    <!--end::Menu item-->
    <!--begin::Menu item-->
    <div class="menu-item px-5">
        <a wire:navigate href="{{ route('transaction') }}" wire:navigate class="menu-link px-5">
            <span class="menu-text">Transaksi Saya</span>
            <span class="menu-badge">
                <span class="badge badge-light-danger badge-circle fw-bold fs-7">{{ $this->ongoingTransactionCounts }}</span>
            </span>
        </a>
    </div>
    <!--end::Menu item-->
    <!--begin::Menu separator-->
    <div class="separator my-2"></div>
    <!--end::Menu separator-->
    <!--begin::Menu item-->
    <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'click'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
        <a href="#" class="menu-link px-5">
			<span class="menu-title position-relative">Tema 
			<span class="ms-5 position-absolute translate-middle-y top-50 end-0">{!! getIcon('night-day', 'theme-light-show fs-2') !!} {!! getIcon('moon', 'theme-dark-show fs-2') !!}</span></span>
		</a>
		@include('partials/theme-mode/__menu')
	</div>
	<!--end::Menu item-->
	<!--begin::Menu item-->
	<div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'click'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
		<a href="#" class="menu-link px-5">
            <span class="menu-title position-relative">Bahasa
                <span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">Bahasa Indonesia
                    <img class="w-15px h-15px rounded-1 ms-2" src="{{ image('flags/indonesia.svg') }}" alt="" /></span>
            </span>
        </a>
        <!--begin::Menu sub-->
        <div class="menu-sub menu-sub-dropdown w-175px py-4">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <a href="#" class="menu-link d-flex px-5 active">
                    <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" src="{{ image('flags/indonesia.svg') }}" alt=""/>
                    </span>
                    Bahasa Indonesia</a>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::Menu sub-->
    </div>
    <!--end::Menu item-->
    <!--begin::Menu item-->
    <div class="menu-item px-5">
        <a class="button-ajax menu-link px-5" href="#" wire:click="logout">
            Keluar
        </a>
    </div>
    <!--end::Menu item-->
</div>
<!--end::User account menu-->
