<?php

use App\Models\User;
use App\Models\Master\Branch;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use function Livewire\Volt\{mount, state, rules, usesFileUploads};

usesFileUploads();

state([
    'name',
    'email',
    'branch_id',
    'phone',
    'role',             
    'roles' => [],      
    'branches' => [],   
    'avatar',
    'saved_avatar',
    'user_id' => null,  
    'edit_mode' => false,
]);

rules(fn() => [
    'name' => 'required|string',
    'email' => 'required|email',
    'branch_id' => 'nullable',
    'phone' => 'required',
    'role' => 'required|string',
    'avatar' => 'nullable|sometimes|image|max:1024',
]);

$listeners = [
    'delete_user' => 'deleteUser',
    'update_user' => 'updateUser',
    'new_user' => 'hydrate',
];

mount(function () {
    $this->branches = Branch::all();

    $currentUser = Auth::user();
    $roles = Role::all();

    // Filter role sesuai role user login
    if ($currentUser->hasRole('Super Admin')) {
        $roles = $roles->whereIn('name', ['Super Admin', 'Owner', 'Cabang', 'Pegawai']);
    } elseif ($currentUser->hasRole('Owner')) {
        $roles = $roles->whereIn('name', ['Cabang', 'Pegawai']);
    } elseif ($currentUser->hasRole('Cabang')) {
        $roles = $roles->whereIn('name', ['Pegawai']);
    } else {
        $roles = collect();
    }

    $roles_description = [
        'Super Admin' => 'Best for business owners and company administrators',
        'Owner' => 'Best for developers or people primarily using the API',
        'Cabang' => 'Best for people who need full access to analytics data, but don\'t need to update business settings',
        'Pegawai' => 'Best for employees who regularly refund payments and respond to disputes',
    ];

    foreach ($roles as $i => $role) {
        $roles[$i]->description = $roles_description[$role->name] ?? '';
    }

    $this->roles = $roles;
});

$submit = function () {
    $this->validate();

    $data = [
        'name' => $this->name,
        'email' => $this->email,
        'branch_id' => $this->branch_id,
        'phone' => $this->phone,
        'avatar' => $this->avatar
    ];

    // Handle avatar upload
        if ($this->avatar) {
        $filename = $this->avatar->store('promos', 'public');
        $data['avatar'] = $filename;
    }

    if (!$this->edit_mode) {
        // Password sementara (hash email atau random)
        $data['password'] = Hash::make($this->email);
        $user = User::castAndCreate($data);

        // Assign role
        $user->assignRole($this->role);

        // Kirim reset link
        Password::sendResetLink($user->only('email'));

        $this->dispatch('success', __('New user created'));
    } else {
        $user = User::findOrFail($this->user_id);

        $user->update($data);

        // Update role
        $user->syncRoles($this->role);

        $this->dispatch('success', __('User updated'));
    }

    // Reset form
    $this->reset([
        'name',
        'email',
        'branch_id',
        'phone',
        'role',
        'avatar',
        'saved_avatar',
        'user_id',
        'edit_mode'
    ]);
};

?>

<div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_user_header">
                
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    {!! getIcon('cross', 'fs-1') !!}
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_user_form" class="form" wire:submit="submit">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_add_user_header"
                        data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Avatar</label>
                            <style>
                                .image-input-placeholder {
                                    background-image: url('{{ image('svg/files/blank-image.svg') }}');
                                }

                                [data-bs-theme="dark"] .image-input-placeholder {
                                    background-image: url('{{ image('svg/files/blank-image-dark.svg') }}');
                                }
                            </style>
                            <div class="image-input image-input-outline image-input-placeholder {{ $this->avatar || $this->saved_avatar ? '' : 'image-input-empty' }}"
                                data-kt-image-input="true">
                                <div class="image-input-wrapper w-125px h-125px"
                                    style="background-image: url({{ $this->avatar ? $this->avatar->temporaryUrl() : $this->saved_avatar }});">
                                </div>
                                <label
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                    {!! getIcon('pencil', 'fs-7') !!}
                                    <input type="file" wire:model="avatar" name="avatar" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="avatar_remove" />
                                </label>
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                    {!! getIcon('cross', 'fs-2') !!}
                                </span>
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                    {!! getIcon('cross', 'fs-2') !!}
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                            @error('avatar')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                            <input type="text" wire:model="name" name="name"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Full name" />
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" wire:model="email" name="email"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="example@domain.com" />
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Phone</label>
                            <input type="number" wire:model="phone" name="phone"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="08..." />
                            @error('phone')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-7">
                            <label class="required fw-semibold fs-6 mb-5">Role</label>
                            @error('role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            @forelse($this->roles as $role)
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3"
                                            id="kt_modal_update_role_option_{{ $role->id }}" wire:model="role" name="role"
                                            type="radio" value="{{ $role->name }}" />
                                        <label class="form-check-label" for="kt_modal_update_role_option_{{ $role->id }}">
                                            <div class="fw-bold text-gray-800">
                                                {{ ucwords($role->name) }}
                                            </div>
                                            <div class="text-gray-600">
                                                {{ $role->description }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @if (!$loop->last)
                                    <div class='separator separator-dashed my-5'></div>
                                @endif
                            @empty
                                <div class="text-gray-600">No assignable roles found.</div>
                            @endforelse
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Branch</label>
                            <select wire:model="branch_id" name="branch_id" class="form-select form-select-solid"
                                data-control="select2" data-hide-search="true" data-placeholder="Select Branch">
                                <option value="">Select Branch</option>
                                @foreach ($this->branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal"
                            wire:loading.attr="disabled">Discard
                        </button>
                        <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                            <span class="indicator-label" wire:loading.remove>Submit</span>
                            <span class="indicator-progress" wire:loading wire:target="submit">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>