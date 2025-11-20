<?php
use App\Models\Contact;
use function Livewire\Volt\{rules, state};

state(['nama', 'email', 'subjek', 'pesan']);
rules(fn () => [
    'nama' => 'required|string|max:255',
    'email' => 'required|email|max:255',
    'subjek' => 'required|string|max:255',
    'pesan' => 'required|string|max:1000',
]);

$send = function() {
    $this->validate();
    Contact::create([
        'name' => $this->nama,
        'email' => $this->email,
        'subject' => $this->subjek,
        'message' => $this->pesan,
    ]);
    $this->reset();
    session()->flash('message', 'Pesan berhasil dikirim!');
};
?>

<x-default-layout>
    @auth
        @role('Super Admin|Owner|Cabang|Pegawai')
            <livewire:contact.admin />
        @else
            <livewire:contact.consumer />
        @endrole
    @else
        <livewire:contact.consumer />
    @endauth
</x-default-layout>