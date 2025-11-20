<?php

?>

<div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
    <h2 class="text-2xl font-semibold mb-6">Tambah Cabang Baru</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <form wire:submit="storeBranch" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Cabang</label>
                <input wire:model="name" type="text" id="name"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                <input wire:model="slug" type="text" id="slug"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Contoh: pusat-jakarta">
                @error('slug') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Kategori</label>
                <input wire:model="category" type="text" id="category"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Contoh: Sewa Drone, iPhone">
                @error('category') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="ig" class="block text-sm font-medium text-gray-700">Instagram Handle</label>
                <input wire:model="ig" type="text" id="ig"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('ig') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="gmaps" class="block text-sm font-medium text-gray-700">Google Maps URL</label>
                <input wire:model="gmaps" type="url" id="gmaps"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('gmaps') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                <input wire:model="phone" type="text" id="phone"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="percentage" class="block text-sm font-medium text-gray-700">Persentase (Opsional)</label>
                <input wire:model="percentage" type="text" id="percentage"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('percentage') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="is_hq" class="block text-sm font-medium text-gray-700">Kantor Pusat (HQ)</label>
                <select wire:model="is_hq" id="is_hq"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="n">Tidak</option>
                    <option value="y">Ya</option>
                </select>
                @error('is_hq') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="st" class="block text-sm font-medium text-gray-700">Status</label>
                <select wire:model="st" id="st"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="a">Active</option>
                    <option value="i">Inactive</option>
                </select>
                @error('st') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="lat" class="block text-sm font-medium text-gray-700">Latitude (Opsional)</label>
                <input wire:model="lat" type="text" id="lat"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('lat') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="lng" class="block text-sm font-medium text-gray-700">Longitude (Opsional)</label>
                <input wire:model="lng" type="text" id="lng"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('lng') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700">Kode Pos (Opsional)</label>
                <input wire:model="postal_code" type="text" id="postal_code"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('postal_code') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Idealnya, kolom ini akan menggunakan dropdown dinamis yang terhubung ke tabel country, state, dll. --}}
            <div>
                <label for="country_id" class="block text-sm font-medium text-gray-700">Country ID (Opsional)</label>
                <input wire:model="country_id" type="number" id="country_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('country_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="state_id" class="block text-sm font-medium text-gray-700">State ID (Opsional)</label>
                <input wire:model="state_id" type="number" id="state_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('state_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="city_id" class="block text-sm font-medium text-gray-700">City ID (Opsional)</label>
                <input wire:model="city_id" type="number" id="city_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('city_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="subdistrict_id" class="block text-sm font-medium text-gray-700">Subdistrict ID
                    (Opsional)</label>
                <input wire:model="subdistrict_id" type="number" id="subdistrict_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('subdistrict_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="village_id" class="block text-sm font-medium text-gray-700">Village ID (Opsional)</label>
                <input wire:model="village_id" type="number" id="village_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                @error('village_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                <textarea wire:model="address" id="address" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                @error('address') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end mt-6">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan Cabang</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>