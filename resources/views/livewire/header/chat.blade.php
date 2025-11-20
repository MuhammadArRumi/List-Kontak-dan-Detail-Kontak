<?php
use function Livewire\Volt\{state};

state([
    'selectedThreadId' => null
]);

// Inisialisasi threadId dari parameter URL jika ada
if (request()->query('thread_id')) {
    $this->selectedThreadId = request()->query('thread_id');
}

$selectThread = function($threadId){
    $this->selectedThreadId = $threadId;
};
?>

<div id="kt_drawer_chat" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="chat" 
     data-kt-drawer-activate="true" data-kt-drawer-overlay="true" 
     data-kt-drawer-width="{default:'auto' ,'sm': '300px', 'md': '500px'}" data-kt-drawer-direction="end" 
     data-kt-drawer-toggle="#kt_drawer_chat_toggle" data-kt-drawer-close="#kt_drawer_chat_close" 
     wire:ignore.self>
    @if($this->selectedThreadId)
        <livewire:chat.message :thread-id="$selectedThreadId" />
    @else
        <livewire:chat.list />
    @endif
</div>

<script>
document.addEventListener('livewire:load', function () {
    // Buka drawer secara otomatis jika thread_id ada di URL
    @if(request()->query('thread_id'))
        const toggle = document.querySelector('#kt_drawer_chat_toggle');
        if (toggle) {
            toggle.click();
        }
    @endif
});
</script>