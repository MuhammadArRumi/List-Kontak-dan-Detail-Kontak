<?php
use App\Models\ProductBranch;
use function Livewire\Volt\{computed, mount, state, rules};
state([
    'product',
    'branch',
    'selectedVariant',
    'selectedColor',
    'selectedStorage',
]);
state([
    'variants' => fn() => ProductBranch::with(['color', 'storage', 'branch'])
        ->where('product_id', $this->product->id)
        ->where('branch_id', $this->branch->id)
        ->where('is_publish', 1)
        ->get(),
]);
mount(function () {
    if ($this->variants->count() > 0) {
        $this->selectedVariant = $this->variants->first();
        $this->selectedColor = $this->selectedVariant->color_id;
        $this->selectedStorage = $this->selectedVariant->storage_id;
    }
});
$getProductImage = function ($variant) {
    if ($variant->color) {
        $colorImage = asset('storage/product/' . $this->product->slug . '-' . Str::slug($variant->color->value) . '.png');
        if ($this->checkImageExists($colorImage)) {
            return $colorImage;
        }
    }
    return $this->product->image;
};

$checkImageExists = function ($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200');
};
?>
<div>
    <div class="product-gallery">
        <img src="{{ $this->getProductImage($this->selectedVariant) }}" 
                class="img-fluid w-a" 
                alt="{{ $this->product->name }}"
                loading="lazy">
    </div>
    <div class="card mt-10">
        <div class="card-body p-8">
            <div class="d-flex justify-content-between align-items-center mb-6">
                <h4 class="mb-0">Ulasan</h4>
                @if($this->product->ratingsCount() > 0)
                    <a href="#all-reviews" class="btn btn-sm btn-light-primary">
                        Lihat Semua ({{ $this->product->ratingsCount() }})
                    </a>
                @endif
            </div>
            
            @if($this->product->ratingsCount() > 0)
                <!-- Rating Summary -->
                <div class="row mb-8">
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        <div class="display-4 fw-bold text-primary">{{ number_format($this->product->averageRating(), 1) }}</div>
                        <div class="rating-stars mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="ki-{{ $i <= $this->product->averageRating() ? 'filled text-warning' : 'outline' }} ki-star"></i>
                            @endfor
                        </div>
                        <div class="text-muted">Berdasarkan {{ $this->product->ratingsCount() }} ulasan</div>
                    </div>
                    <div class="col-md-8">
                        @for($i = 5; $i >= 1; $i--)
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">{{ $i }} <i class="ki-filled ki-star text-warning"></i></span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    @php
                                        $count = $this->product->ratings->where('rating', $i)->count();
                                        $percentage = $this->product->ratingsCount() > 0 ? ($count / $this->product->ratingsCount()) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="ms-2 text-muted">{{ $count }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
                
                <!-- Featured Reviews -->
                <div class="mb-8">
                    <h5 class="mb-4">Ulasan Teratas</h5>
                    <div class="row g-6">
                        @foreach($this->product->ratings->where('status', 'approved')->sortByDesc('created_at')->take(5) as $rating)
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-35px symbol-circle me-3">
                                    @if($rating->is_anonymous)
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">
                                            A
                                        </span>
                                    @else
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">
                                            {{ substr($rating->user->name, 0, 1) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold">
                                        {{ $rating->is_anonymous ? 'Pengguna Anonim' : $rating->user->name }}
                                    </div>
                                    <div class="text-muted fs-7">
                                        {{ $rating->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="rating-stars mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="ki-{{ $i <= $rating->rating ? 'filled text-warning' : 'outline' }} ki-star"></i>
                                @endfor
                            </div>
                            <p class="mb-3">{{ $rating->review }}</p>
                            @if($rating->medias->count() > 0)
                                <div class="review-media">
                                    @foreach($rating->medias->take(3) as $media)
                                        <img src="{{ $media->image }}" alt="Review media" class="img-thumbnail">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- All Reviews Link -->
                <div class="text-center">
                    <a href="#all-reviews" class="btn btn-light-primary">Lihat Semua Ulasan</a>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="ki-filled ki-message-text-2 fs-2x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada ulasan untuk produk ini</p>
                    @auth
                        <button class="btn btn-primary">Beri Ulasan</button>
                    @endauth
                </div>
            @endif
        </div>
    </div>
    @if($this->product->ratingsCount() > 0)
        <div class="card mt-10" id="all-reviews">
            <div class="card-body p-8">
                <h4 class="mb-6">Semua Ulasan</h4>
                <div class="row g-6">
                    @foreach($this->product->ratings->where('status', 'approved')->sortByDesc('created_at') as $rating)
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-35px symbol-circle me-3">
                                    @if($rating->is_anonymous)
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">
                                            A
                                        </span>
                                    @else
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">
                                            {{ substr($rating->user->name, 0, 1) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold">
                                        {{ $rating->is_anonymous ? 'Pengguna Anonim' : $rating->user->name }}
                                    </div>
                                    <div class="text-muted fs-7">
                                        {{ $rating->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="rating-stars mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="ki-{{ $i <= $rating->rating ? 'filled text-warning' : 'outline' }} ki-star"></i>
                                @endfor
                            </div>
                            <p class="mb-3">{{ $rating->review }}</p>
                            @if($rating->medias->count() > 0)
                                <div class="review-media">
                                    @foreach($rating->medias as $media)
                                        <img src="{{ $media->image }}" alt="Review media" class="img-thumbnail">
                                    @endforeach
                                </div>
                            @endif
                            @if($rating->rating > 3)
                                <div class="mt-3">
                                    <span class="badge bg-success bg-opacity-10 text-success">
                                        <i class="ki-filled ki-check-circle me-1"></i>Merekomendasikan produk ini
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>