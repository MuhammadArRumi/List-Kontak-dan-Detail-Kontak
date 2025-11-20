<?php

use Carbon\Carbon;
use App\Models\User;
use Cmgmyr\Messenger\Models\Thread;
use Cmgmyr\Messenger\Models\Message;
use App\Notifications\NewMessagesNotification;
use function Livewire\Volt\{computed, mount, state};

state([
    'selectedThreadId' => null,
    'threadId'         => null,
    'messages'         => [],
    'newMessage'       => null,
    'thread'           => null,
    'recipient'        => null,
]);

mount(function () {
    $this->selectedThreadId = $this->threadId;
    $this->threadId         = $this->threadId;
    $this->loadThread();
    $this->loadMessages();
    $this->loadRecipient();
});

$loadThread = computed(function () {
    $this->thread = Thread::findOrFail($this->threadId);
});

$loadMessages = function () {
    $thread = Thread::findOrFail($this->threadId);

    if (
        !Auth::user()->hasRole(['Super Admin', 'Owner']) &&
        !$thread->hasParticipant(Auth::user()->id)
    ) {
        abort(403);
    }

    $this->messages = $thread->messages()->with('user')->get()->toArray();
};

$loadRecipient = function () {
    $thread       = Thread::findOrFail($this->threadId);
    $participants = $thread
        ->participants()
        ->where('user_id', '!=', Auth::id())
        ->get();

    if ($participants->count() > 0) {
        $this->recipient = User::find($participants->first()->user_id);
    }
};

$sendMessage = function () {
    $this->validate([
        'newMessage' => 'required|string|max:5000',
    ]);

    $message = Message::create([
        'thread_id' => $this->threadId,
        'user_id'   => Auth::user()->id,
        'body'      => $this->newMessage,
    ]);

    $thread       = Thread::findOrFail($this->threadId);
    $participants = $thread
        ->participants()
        ->where('user_id', '!=', Auth::user()->id)
        ->get();

    foreach ($participants as $participant) {
        $user = User::find($participant->user_id);
        if ($user) {
            $user->notify(new NewMessagesNotification($message, $thread));
        }
    }

    $this->newMessage = '';
    $this->loadMessages();
};

$resetThread = function () {
    $this->selectedThreadId = null;
};
?>

<div class="card w-100 border-0 rounded-0 mb-15" id="kt_drawer_chat_messenger">
    <!-- Header -->
    <div class="card-header pe-5" id="kt_drawer_chat_messenger_header">
        <div class="card-title">
              <button class="btn btn-sm btn-icon btn-active-light-primary me-3" type="button"
                    data-bs-toggle="tooltip" wire:click="$parent.selectThread(null)">
                    <i class="ki-solid ki-black-left fs-3"></i>
                </button>
            <div class="d-flex justify-content-center flex-column me-3">
                @if ($recipient)
                    <a href="#"
                        class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1">{{ $recipient->name }}</a>
                    <div class="mb-0 lh-1">
                        <span class="badge badge-success badge-circle w-10px h-10px me-1"></span>
                        <span class="fs-7 fw-semibold text-muted">Active</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-toolbar">
            <div class="me-0">
                <button class="btn btn-sm btn-icon btn-active-color-primary" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-dots-square fs-2"></i>
                </button>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                    data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Contacts</div>
                    </div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_users_search">Add Contact</a>
                    </div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link flex-stack px-3" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_invite_friends">
                            Invite Contacts
                            <span class="ms-2" data-bs-toggle="tooltip"
                                title="Specify a contact email to send an invitation">
                                <i class="ki-outline ki-information fs-7"></i>
                            </span>
                        </a>
                    </div>
                    <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                        <a href="#" class="menu-link px-3">
                            <span class="menu-title">Groups</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="menu-sub menu-sub-dropdown w-175px py-4">
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-bs-toggle="tooltip"
                                    title="Coming soon">Create Group</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-bs-toggle="tooltip"
                                    title="Coming soon">Invite Members</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-bs-toggle="tooltip"
                                    title="Coming soon">Settings</a>
                            </div>
                        </div>
                    </div>
                    <div class="menu-item px-3 my-1">
                        <a href="#" class="menu-link px-3" data-bs-toggle="tooltip"
                            title="Coming soon">Settings</a>
                    </div>
                </div>
            </div>
            <div wire:click="resetThread()" class="btn btn-sm btn-icon btn-active-color-primary"
                id="kt_drawer_chat_close">
                <i class="ki-outline ki-cross-square fs-2"></i>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body flex-grow-1 overflow-auto" id="kt_drawer_chat_messenger_body"
        wire:poll="loadMessages()">
        <div class="scroll-y me-n5 pe-5" data-kt-element="messages" data-kt-scroll="true"
            data-kt-scroll-activate="true" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_drawer_chat_messenger_header, #kt_drawer_chat_messenger_footer"
            data-kt-scroll-wrappers="#kt_drawer_chat_messenger_body" data-kt-scroll-offset="0px">
            @foreach ($messages as $message)
                <div
                    class="d-flex justify-content-{{ $message['user_id'] == auth()->id() ? 'end' : 'start' }} mb-10">
                    <div
                        class="d-flex flex-column align-items-{{ $message['user_id'] == auth()->id() ? 'end' : 'start' }}">
                        <div class="d-flex align-items-center mb-2">
                            @php
                                $diff = Carbon::parse($message['created_at'])->diffForHumans();
                                $user = User::find($message['user_id']);
                            @endphp
                            <div class="d-flex align-items-center">
                                <img alt="Pic" src="{{ $user->image }}" class="rounded-circle"
                                    style="width:35px; height:35px; object-fit:cover;" />
                            </div>
                            <div class="ms-3">
                                @role('Super Admin|Owner|Cabang')
                                    <a href="{{ route('user.show', ['user' => $user]) }}"
                                        class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">
                                        {{ $user->name }}
                                    </a>
                                @else
                                    <a href="#"
                                        class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">
                                        {{ $user->name }}
                                    </a>
                                @endrole
                                <span class="text-muted fs-7 mb-1">{{ $diff }}</span>
                            </div>
                        </div>
                        <div class="p-5 rounded bg-light-{{ $message['user_id'] == auth()->id() ? 'primary' : 'info' }} text-gray-900 fw-semibold mw-lg-400px text-start"
                            data-kt-element="message-text">
                            {{ $message['body'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer py-3" id="kt_drawer_chat_messenger_footer">
        <form wire:submit.prevent="sendMessage" class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center me-2">
                <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button"
                    data-bs-toggle="tooltip" title="Coming soon">
                    <i class="ki-solid ki-paper-clip fs-3"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button"
                    data-bs-toggle="tooltip" title="Coming soon">
                    <i class="ki-solid ki-cloud fs-3"></i>
                </button>
            </div>

            <!-- Textarea -->
            <textarea class="form-control form-control-flush flex-grow-1"
                wire:model="newMessage"
                placeholder="Ketik pesan..."
                rows="1"
                style="resize:none; min-height:40px; max-height:120px; overflow-y:auto;">
            </textarea>

            <!-- Tombol Kirim -->
            <button class="btn btn-primary" type="submit">
                <i class="ki-solid ki-send"></i>
            </button>
        </form>
    </div>
</div>
