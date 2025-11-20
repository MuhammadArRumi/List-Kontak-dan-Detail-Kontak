<?php
use Livewire\Volt\Component;
use App\Models\Master\Category;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $categories;
    public function mount()
    {
        $this->categories = Category::where('st', 'a')
        ->withCount('products')
        ->orderBy('name')
        ->get();
    }
    public function placeholder()
    {
        return view('livewire.skeleton.category-home');
    }
};
?>
<div class="home-category mt-10 mb-10 d-flex flex-wrap gap-3" aria-label="dynamic icon wrapper" role="region">
    @foreach($this->categories as $category)
        <a wire:navigate href="{{ route('category.show', ['category' => $category]) }}"
            class="home-item-category d-flex align-items-center px-3 py-2 border rounded" tabindex="0" role="button">
            <img src="{{ $category->image ?? asset('assets/media/placeholder/category.png') }}"
                    alt="{{ $category->name }}" class="me-2" width="24">
            <span>{{ $category->name }}</span>
        </a>
    @endforeach
</div>