<x-nav>
    <x-nav-item href="{{ route('profile.edit') }}" active="{{request()->is('profile/account/edit')}}">
        Biodata Diri
    </x-nav-item>
    @role('Konsumen|Onboarding')
    <x-nav-item href="{{ route('profile.address') }}" active="{{request()->is('profile/address')}}">
        Daftar Alamat
    </x-nav-item>
    <x-nav-item href="{{ route('profile.family') }}" active="{{request()->is('profile/family')}}">
        Daftar Keluarga
    </x-nav-item>
    @endrole
    <x-nav-item href="{{ route('profile.bank') }}" active="{{request()->is('profile/bank')}}">
        Rekening Bank
    </x-nav-item>
</x-nav>