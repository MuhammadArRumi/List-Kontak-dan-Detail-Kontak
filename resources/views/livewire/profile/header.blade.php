<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, state, usesFileUploads};
use App\Models\UserProfile; // Import UserProfile model for creating profiles

usesFileUploads();
state(['user' => fn() => \App\Models\User::where('id', Auth::user()->id)->first()]);
state([
    'avatar' => '',
    'remove_avatar' => '',
]);

mount(function () {
    $this->avatar = $this->user->avatar ?? '';
});

$updatedAvatar = function() {
    $this->validate([
        'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
    ]);

    if ($this->avatar) {
        $path = $this->avatar->store('avatar', 'public');
        
        if (File::exists($this->avatar->getRealPath())) {
            File::delete($this->avatar->getRealPath());
        }
        
        if ($this->user->avatar && Storage::disk('public')->exists($this->user->avatar)) {
            Storage::disk('public')->delete($this->user->avatar);
        }
        
        $this->user->castAndUpdate(['avatar' => $path]);
    }
};

$removeAvatar = function() {
    try {
        $avatarPath = Auth::user()->avatar;
        if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
            $this->user->castAndUpdate(['avatar' => null]);
            logger()->info("Avatar deleted successfully: {$avatarPath}");
        }
    } catch (\Exception $e) {
        logger()->error("Failed to delete avatar: " . $e->getMessage());
    }
};
?>
<div>
    <div class="d-none d-lg-block">
        <div class="card mb-5 mb-xl-10">
            <div class="card-body pt-9 pb-0">
                <div class="d-flex flex-wrap flex-sm-nowrap">
                    <!-- Profile Picture -->
                    <div class="me-7 mb-4">
                        @if($this->user->st == 'pending')
                            <div class="image-input {{ !Auth::user()->avatar ? 'image-input-empty image-input-placeholder' : 'image-input-circle' }}" data-kt-image-input="true">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url({{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : '' }})"></div>
                                <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change"
                                    data-bs-toggle="tooltip"
                                    data-bs-dismiss="click"
                                    title="Pilih Foto">
                                    <i class="ki-filled ki-pencil fs-6"></i>
                                    <input type="file" wire:model="avatar" accept=".png,.jpg,.jpeg" />
                                    <input type="hidden" name="avatar_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel"
                                    data-bs-toggle="tooltip"
                                    data-bs-dismiss="click"
                                    title="Batalkan">
                                    <i class="ki-filled ki-cross fs-3"></i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove"
                                    data-bs-toggle="tooltip"
                                    data-bs-dismiss="click"
                                    wire:click="removeAvatar"
                                    title="Hapus">
                                    <i class="ki-filled ki-trash fs-3"></i>
                                </span>
                            </div>
                        @else
                            <div class="symbol symbol-100px symbol-circle">
                                <div class="symbol-label" style="background-image:url({{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : '' }})"></div>
                            </div>
                        @endif
                    </div>
                    <!--begin::Info-->
                    <div class="flex-grow-1">
                        <!--begin::Title-->
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                            <!--begin::User-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <div class="d-flex align-items-center mb-2">
                                    <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">
                                        {{ Auth::user()->name }}
                                    </a>
                                    <a href="#">
                                        {!! getIcon('verify', 'fs-2 fs-md-1 text-primary') !!}
                                    </a>
                                </div>
                                <!--end::Name-->
                                <!--begin::Info-->
                                <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                    <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                        {!! getIcon('phone', 'fs-4 me-1') !!}
                                        {{ '+62' . Auth::user()->phone }}
                                    </a>
                                    <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                        {!! getIcon('sms', 'fs-4 me-1') !!}
                                        {{ Auth::user()->email }}
                                    </a>
                                </div>
                                <!--end::Info-->
                            </div>
                            <!--end::User-->
                            <!--begin::Actions-->
                            <div class="d-flex my-4">
                                <a href="#" class="btn btn-sm btn-light me-2" id="kt_user_follow_button">
                                    <i class="ki-duotone ki-check fs-3 d-none"></i>
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Ubah Kata Sandi</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Please wait... 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </a>
                                <a href="#" class="btn btn-sm btn-light me-2" id="kt_user_follow_button">
                                    <i class="ki-duotone ki-check fs-3 d-none"></i>
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Buat PIN {{ config('app.name') }}</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Please wait... 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </a>
                                <a href="#" class="btn btn-sm btn-light-danger me-2" id="kt_user_follow_button">
                                    <i class="ki-duotone ki-check fs-3 d-none"></i>
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Tutup Akun</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Please wait... 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </a>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Title-->
                        <!--begin::Stats-->
                        <div class="d-flex flex-wrap flex-stack">
                            <!--begin::Wrapper-->
                            <div class="d-flex flex-column flex-grow-1 pe-8">
                                <!--begin::Stats-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Stat-->
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        @role('Super Admin|Owner')
                                        <!--begin::Number-->
                                        <div class="d-flex align-items-center">
                                            {{-- <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i> --}}
                                            <div class="fs-2 fw-bold" data-kt-countup-separator="," data-kt-countup-prefix="Rp." data-kt-countup="true" data-kt-countup-value="0">0</div>
                                        </div>
                                        <!--end::Number-->
                                        <!--begin::Label-->
                                        <div class="fw-semibold fs-6 text-gray-500">Transaksi minggu ini</div>
                                        <!--end::Label-->
                                        @elserole('Cabang|Pegawai')
                                        <!--begin::Number-->
                                        <div class="d-flex align-items-center">
                                            {{-- <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i> --}}
                                            <div class="fs-2 fw-bold" data-kt-countup-separator="," data-kt-countup-prefix="Rp." data-kt-countup="true" data-kt-countup-value="0">0</div>
                                        </div>
                                        <!--end::Number-->
                                        <!--begin::Label-->
                                        <div class="fw-semibold fs-6 text-gray-500">Transaksi minggu ini</div>
                                        <!--end::Label-->
                                        @elserole('Konsumen|Onboarding')
                                        <!--begin::Number-->
                                        <div class="d-flex align-items-center">
                                            {{-- <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i> --}}
                                            <div class="fs-2 fw-bold" data-kt-countup="true" data-kt-countup-value="{{ $this->user->getPoints() }}">0</div>
                                        </div>
                                        <!--end::Number-->
                                        <!--begin::Label-->
                                        <div class="fw-semibold fs-6 text-gray-500">Poin Saya</div>
                                        <!--end::Label-->
                                        @endrole
                                    </div>
                                    <!--end::Stat-->
                                    <!--begin::Stat-->
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        <!--begin::Number-->
                                        <div class="d-flex align-items-center">
                                            {{-- <i class="ki-duotone ki-arrow-down fs-3 text-danger me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i> --}}
                                            <div class="fs-2 fw-bold" data-kt-countup-start-val="0" data-kt-countup="true" data-kt-countup-separator="," data-kt-countup-prefix="Rp." data-kt-countup-value="{{ number_format($this->user->balance) }}">0</div>
                                        </div>
                                        <!--end::Number-->
                                        <!--begin::Label-->
                                        <div class="fw-semibold fs-6 text-gray-500">Saldo Saya</div>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Stat-->
                                    <!--begin::Stat-->
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        @role('Super Admin|Owner')
                                        @elserole('Cabang|Pegawai')
                                        @elserole('Konsumen|Onboarding')
                                        <!--begin::Number-->
                                        <div class="d-flex align-items-center">
                                            {{-- <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i> --}}
                                            <div class="fs-2 fw-bold">{{ $this->user->getUserAchievements()[0]->name }}</div>
                                        </div>
                                        <!--end::Number-->
                                        <!--begin::Label-->
                                        <div class="fw-semibold fs-6 text-gray-500">Membership</div>
                                        <!--end::Label-->
                                        @endrole
                                    </div>
                                    <!--end::Stat-->
                                </div>
                                <!--end::Stats-->
                            </div>
                            <!--end::Wrapper-->
                            <!--begin::Progress-->
                            <div class="d-flex align-items-center w-200px w-sm-300px flex-column mt-3">
                                <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                                    <span class="fw-semibold fs-6 text-gray-500">Profile Compleation</span>
                                    <span class="fw-bold fs-6">50%</span>
                                </div>
                                <div class="h-5px mx-3 w-100 bg-light mb-3">
                                    <div class="bg-success rounded h-5px" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <!--end::Progress-->
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Info-->
                </div>
                <!--end::Details-->
                <!--begin::Navs-->
                <livewire:profile.nav/>
                <!--end::Navs-->
            </div>
        </div>
    </div>
    <div class="d-block d-lg-none">
        @if(request()->is('profile/account/edit'))
        <div class="text-center mb-10">
            @if($this->user->st == 'pending')
                <div class="image-input {{ !Auth::user()->avatar ? 'image-input-empty image-input-placeholder' : 'image-input-circle' }}" data-kt-image-input="true">
                    <div class="image-input-wrapper w-125px h-125px" style="background-image: url({{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : '' }})"></div>
                    <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="change"
                        data-bs-toggle="tooltip"
                        data-bs-dismiss="click"
                        title="Pilih Foto">
                        <i class="ki-filled ki-pencil fs-6"></i>
                        <input type="file" wire:model="avatar" accept=".png,.jpg,.jpeg" />
                        <input type="hidden" name="avatar_remove" />
                    </label>
                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="cancel"
                        data-bs-toggle="tooltip"
                        data-bs-dismiss="click"
                        title="Batalkan">
                        <i class="ki-filled ki-cross fs-3"></i>
                    </span>
                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                        data-kt-image-input-action="remove"
                        data-bs-toggle="tooltip"
                        data-bs-dismiss="click"
                        wire:click="removeAvatar"
                        title="Hapus">
                        <i class="ki-filled ki-trash fs-3"></i>
                    </span>
                </div>
            @else
                <div class="symbol symbol-100px symbol-circle">
                    <div class="symbol-label" style="background-image:url({{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : '' }})"></div>
                </div>
            @endif
        </div>
        @endif
    </div>
</div>