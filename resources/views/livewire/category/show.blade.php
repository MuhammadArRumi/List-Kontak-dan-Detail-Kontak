<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Master\Category;
use App\Models\Master\Brand;

new class extends Component
{
    public Category $category;

    public $brand = '';
    public $minPrice = '';
    public $maxPrice = '';
    public $rating = '';

    public function mount(Category $category)
    {
        $this->category = $category;
    }
    
    public function getProductsProperty()
    {
        return Product::where('category_id', $this->category->id)
            ->when($this->brand, fn($q) => $q->where('brand_id', $this->brand))
            ->when($this->minPrice, fn($q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn($q) => $q->where('price', '<=', $this->maxPrice))
            ->when($this->rating, fn($q) => $q->where('rating', '>=', $this->rating))
            ->get();
    }
};
?>

<div class="filters mb-6 flex gap-4">
    <!-- brand -->
    <select wire:model="brand" class="border rounded p-2">
        <option value="">-- Semua Brand --</option>
        @foreach(\App\Models\Master\Brand::orderBy('name')->get() as $b)
            <option value="{{ $b->id }}">{{ $b->name }}</option>
        @endforeach
    </select>

    <!-- harga -->
    <input type="number" wire:model="minPrice" placeholder="Harga min" class="border rounded p-2">
    <input type="number" wire:model="maxPrice" placeholder="Harga max" class="border rounded p-2">

    <!-- rating -->
    <select wire:model="rating" class="border rounded p-2">
        <option value="">-- Rating minimal --</option>
        <option value="1">1+</option>
        <option value="2">2+</option>
        <option value="3">3+</option>
        <option value="4">4+</option>
        <option value="5">5</option>
    </select>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
    @forelse($this->products as $product)
        <div class="border rounded p-4">
            <img src="{{ asset('storage/' . $product->thumbnail) }}" 
                 alt="{{ $product->name }}" class="w-full h-40 object-cover mb-2">
            <h3 class="font-semibold">{{ $product->name }}</h3>
            <p class="text-sm text-gray-600">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="text-yellow-500">⭐ {{ $product->rating ?? '-' }}</p>
        </div>
    @empty
        <p class="col-span-4 text-center text-gray-500">Produk tidak ditemukan</p>
    @endforelse
</div>
