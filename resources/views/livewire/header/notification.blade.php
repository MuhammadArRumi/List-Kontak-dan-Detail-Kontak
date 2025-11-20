<?php
use function Livewire\Volt\{state};

state([
    'unreadCounts' => fn() => Auth::check() ? Auth::user()->unreadNotifications->count() : 0,
    'notifications' => fn() => Auth::check() ? Auth::user()->notifications()->latest()->take(10)->get() : collect(),
]);
$headerloadNotification = function() {
    if (Auth::check()) {
        $this->unreadCounts = Auth::user()->unreadNotifications->count();
        $this->notifications = Auth::user()->notifications()->latest()->take(10)->get();
    }
};
// Fungsi untuk menandai notifikasi sebagai dibaca
$markAsRead = function($notificationId) {
    if (Auth::check()) {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->headerloadNotification();
        }
    }
};

// Fungsi untuk menandai semua notifikasi sebagai dibaca
$markAllAsReads = function() {
    if (Auth::check()) {
        Auth::user()->unreadNotifications->markAsRead();
        $this->headerloadNotification();
    }
};

// Fungsi untuk menghapus notifikasi
$deleteNotifications = function($notificationId) {
    if (Auth::check()) {
        Auth::user()->notifications()->where('id', $notificationId)->delete();
        $this->headerloadNotification();
    }
};
?>
<div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true" id="kt_menu_notifications">
    <!--begin::Heading-->
    <div class="d-flex flex-column bgi-no-repeat rounded-top" style="background-image:url('{{asset('assets/media/bg/menu-header-dark.png')}}')">
        <!--begin::Title-->
        <h3 class="text-white fw-semibold px-9 mt-10 mb-6">
            Notifikasi
            @if($this->unreadCounts > 0)
            <span class="fs-8 opacity-75 ps-3">
                {{ $this->unreadCounts }} notifikasi yang belum dibaca
            </span>
            @endif
        </h3>
        <!--end::Title-->
        <!--begin::Tabs-->
        @role('Super Admin|Owner|Cabang|Pegawai')
        <ul class="nav nav-line-tabs nav-line-tabs-2x nav-stretch fw-semibold px-9">
            @role('Super Admin|Owner')
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 opacity-state-100 pb-4 active" data-bs-toggle="tab" href="#kt_topbar_notifications_1">Alerts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_2">Updates</a>
            </li>
            @endrole
            @role('Super Admin')
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_3">Logs</a>
            </li>
            @endrole
        </ul>
        @endrole
        <!--end::Tabs-->
    </div>
    <!--end::Heading-->
    <!--begin::Tab content-->
    <div class="tab-content">
        <!--begin::Tab panel-->
        <div class="tab-pane fade show active" id="kt_topbar_notifications_1" role="tabpanel">
            <!--begin::Items-->
            <div class="scroll-y mh-325px my-5 px-8">
                @forelse($this->notifications as $notification)
                <div class="d-flex flex-stack py-4">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-35px me-4">
                            <span class="symbol-label bg-light-{{ $notification->data['severity'] ?? 'primary' }}">
                                <i class="ki-outline ki-{{ $notification->data['icon'] ?? 'notification' }} fs-2 text-{{ $notification->data['severity'] ?? 'primary' }}"></i>
                            </span>
                        </div>
                        <div class="mb-0 me-2">
                            <a href="#" class="fs-6 text-gray-800 text-hover-{{ $notification->data['severity'] ?? 'primary' }} fw-bold">{{ $notification->data['title'] ?? 'Notification' }}</a>
                            <div class="text-gray-500 fs-7">{{ $notification->data['message'] ?? '' }}</div>
                        </div>
                    </div>
                    <span class="badge badge-light fs-8">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                @empty
                <div class="text-center px-4">
                    <img class="mw-100 mh-200px theme-light-show" alt="image" src="{{asset('assets/media/illustrations/there-is-nothing-here.png')}}" />
                    <img class="mw-100 mh-200px theme-dark-show" alt="image" src="{{asset('assets/media/illustrations/there-is-nothing-here-dark.png')}}" />
                </div>
                @endforelse
            </div>
            @if($this->unreadCounts > 0)
            <div class="py-3 text-center border-top">
                <button wire:click="markAllAsReads" class="btn btn-color-gray-600 btn-active-color-primary">
                    Tandai semua sudah dibaca
                    <i class="ki-outline ki-check fs-5"></i>
                </button>
            </div>
            @endif
        </div>
        <div class="tab-pane fade" id="kt_topbar_notifications_2" role="tabpanel">
            <!--begin::Wrapper-->
            <div class="d-flex flex-column px-9">
                <!--begin::Section-->
                <div class="pt-10 pb-0">
                    <!--begin::Title-->
                    <h3 class="text-gray-900 text-center fw-bold">Get Pro Access</h3>
                    <!--end::Title-->
                    <!--begin::Text-->
                    <div class="text-center text-gray-600 fw-semibold pt-1">Outlines keep you honest. They stoping you from amazing poorly about drive</div>
                    <!--end::Text-->
                    <!--begin::Action-->
                    <div class="text-center mt-5 mb-9">
                        <a href="#" class="btn btn-sm btn-primary px-6" data-bs-toggle="modal" data-bs-target="#kt_modal_upgrade_plan">Upgrade</a>
                    </div>
                    <!--end::Action-->
                </div>
                <!--end::Section-->
                <!--begin::Illustration-->
                <div class="text-center px-4">
                    <img class="mw-100 mh-200px" alt="image" src="" />
                </div>
                <!--end::Illustration-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Tab panel-->
        <!--begin::Tab panel-->
        <div class="tab-pane fade" id="kt_topbar_notifications_3" role="tabpanel">
            <!--begin::Items-->
            <div class="scroll-y mh-325px my-5 px-8">
                <!--begin::Item-->
                <div class="d-flex flex-stack py-4">
                    <!--begin::Section-->
                    <div class="d-flex align-items-center me-2">
                        <!--begin::Code-->
                        <span class="w-70px badge badge-light-success me-4">200 OK</span>
                        <!--end::Code-->
                        <!--begin::Title-->
                        <a href="#" class="text-gray-800 text-hover-primary fw-semibold">New order</a>
                        <!--end::Title-->
                    </div>
                    <!--end::Section-->
                    <!--begin::Label-->
                    <span class="badge badge-light fs-8">Just now</span>
                    <!--end::Label-->
                </div>
                <!--end::Item-->
            </div>
            <!--end::Items-->
            <!--begin::View more-->
            <div class="py-3 text-center border-top">
                <a href="pages/user-profile/activity.html" class="btn btn-color-gray-600 btn-active-color-primary">View All 
                <i class="ki-outline ki-arrow-right fs-5"></i></a>
            </div>
            <!--end::View more-->
        </div>
        <!--end::Tab panel-->
    </div>
    <!--end::Tab content-->
</div>