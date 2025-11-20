<?php
use function Livewire\Volt\{computed, mount, state};
use Cmgmyr\Messenger\Models\Thread;

state([
    'threads' => [],
    'search' => '',
]);

mount(function () {
    $this->loadThreads();
});

$updatedSearch = function () {
    $this->loadThreads();
};

$loadThreads = computed(function () {
    $user = Auth::user();
    $searchTerm = '%' . $this->search . '%';

    if ($user->hasRole(['Super Admin', 'Owner'])) {
        $this->threads = Thread::with(['participants.user'])
            ->where(function ($query) use ($searchTerm) {
                $query->where('subject', 'like', $searchTerm)
                    ->orWhereHas('participants.user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
            })->orderBy('created_at', 'desc')
            ->get();
    } else {
        $this->threads = Thread::forUser($user->id)
            ->where(function ($query) use ($searchTerm) {
                $query->where('subject', 'like', $searchTerm)
                    ->orWhereHas('participants.user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
            })
            ->get();
    }
});
?>
<div class="card w-100 card-flush">
    <!-- Header -->
    <div class="card-header p-4 border-0" id="kt_chat_contacts_header">
        <h1 class="fs-1 fw-bold mb-3">Chats</h1>
        <form class="w-100 position-relative" autocomplete="off">
            <i class="ki-outline ki-magnifier fs-3 text-gray-500 position-absolute top-50 ms-4 translate-middle-y"></i>
            <input type="text"
                   class="form-control form-control-solid ps-10"
                   wire:model.live="search"
                   placeholder="Cari berdasarkan nama atau email..." />
        </form>
    </div>

    <!-- Body -->
    <div class="card-body p-4 mt-3" id="kt_chat_contacts_body" wire:poll.5s>
        @forelse($this->threads as $thread)
            @php
                $otherParticipants = $thread->participants->filter(fn($p) => $p->user_id !== Auth::id());
                $firstParticipant = $otherParticipants->first();
                $user = $firstParticipant->user ?? null;
            @endphp

            <div class="d-flex flex-stack py-3">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-45px symbol-circle">
                        @if($user && $user->image)
                            <img alt="Pic" src="{{ $user->image }}" class="rounded-circle"
                                 style="width:45px; height:45px; object-fit:cover;" />
                        @elseif($user)
                            <span class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        @else
                            <span class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
                                {{ strtoupper(substr($thread->subject, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="ms-4">
                        <a href="#"
                           wire:click="$parent.selectThread({{ $thread->id }})"
                           class="fs-6 fw-bold text-gray-900 text-hover-primary d-block">
                            {{ $user->name ?? $thread->subject }}
                        </a>
                        <div class="fw-semibold text-muted fs-7">
                            {{ $user->email ?? $thread->participants->pluck('email')->implode(', ') }}
                        </div>
                    </div>
                </div>
                <span class="text-muted fs-8">{{ $thread->updated_at->diffForHumans() }}</span>
            </div>

            <div class="separator"></div>
        @empty
            <div class="text-center py-5">
                <p class="text-muted">Belum ada percakapan.</p>
            </div>
        @endforelse
    </div>
</div>
