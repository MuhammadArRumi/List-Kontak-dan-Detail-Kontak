<?php
use App\Models\Rating;
use App\Models\RatingMedia;
use App\Models\Transaction\Rent;
use App\Models\Transaction\Sale;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{mount, rules, state, usesFileUploads};

usesFileUploads();
state(['code', 'rating', 'review']);
state(['media' => [], 'is_anonymous' => false]);
rules(fn() => [
    'rating' => 'required|integer|between:1,5',
    'review' => 'nullable|string|max:1000',
    'media' => ['nullable', 'array', 'max:5'],
    'media.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,webm', 'max:5120'], // 5MB max per file
]);
mount(function () {
    $transaction = Rent::where('code', $this->code)
        ->where('user_id', Auth::id())
        ->where('status', 'completed')
        ->exists() || Sale::where('code', $this->code)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->exists();

    if (!$transaction) {
        $this->dispatch('toast-error', message: 'Anda harus menyelesaikan transaksi terlebih dahulu untuk memberikan rating.');
        return redirect()->back();
    }
});
$removeMedia = function ($index) {
    if (isset($this->media[$index])) {
        unset($this->media[$index]);
        // Reset array keys
        $this->media = array_values($this->media);
    }
};
$cleanupLivewireTempFiles = function () {
    $tempDirectory = storage_path('app/livewire-tmp');

    if (File::isDirectory($tempDirectory)) {
        // Hapus semua file dalam direktori temporary
        File::cleanDirectory($tempDirectory);
    }
};
$save = function () {
    $this->validate();
    if ($this->rating > 3) {
        $status = "approved";
        $would_recommend = 1;
    } elseif ($this->rating < 3) {
        $status = "rejected";
        $would_recommend = 0;
    } else {
        $status = "pending";
        $would_recommend = 0;
    }

    $rent = Rent::where('code', $this->code)
        ->where('user_id', Auth::id())
        ->where('status', 'completed')
        ->first();

    $transaction = $rent;
    if (!$rent) {
        $sale = Sale::where('code', $this->code)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->first();
        $transaction = $sale;
    }

    foreach ($transaction->items as $item) {
        $rating = Rating::castAndCreate([
            'rateable_type' => get_class($transaction),
            'rateable_id' => $transaction->id,
            'product_id' => $item->productBranch->product_id,
            'user_id' => Auth::id(),
            'branch_id' => $transaction->branch_id,
            'rating' => $this->rating,
            'review' => $this->review,
            'status' => $status,
            'would_recommend' => $would_recommend,
            'is_anonymous' => $this->is_anonymous,
        ]);

        $mediaPaths = [];
        if (!empty($this->media)) {
            foreach ($this->media as $file) {
                $path = $file->store('ratings', 'public'); // Folder public/storage/ratings
                $mediaPaths[] = $path;
                RatingMedia::castAndCreate([
                    'rating_id' => $rating->id,
                    'media_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'order' => 0
                ]);
            }
        }
    }

    $this->cleanupLivewireTempFiles();
    $this->dispatch('toast-success', message: 'Rating berhasil disimpan!');
    $this->reset(['rating', 'review']);
    $this->redirect(route('transaction'), navigate: true);
}
?>
<x-form action="save" hasFiles>
    <style>
        .rating-card {
            border-radius: 16px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .rating-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-bottom: 0;
        }

        .star-rating {
            display: flex;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .star {
            font-size: 2.5rem;
            color: #e9ecef;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.2s ease;
            margin: 0 0.25rem;
        }

        .star:hover {
            transform: scale(1.2);
        }

        .star.active {
            color: #ffc107;
        }

        .rating-emoji {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            height: 2.5rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.8rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(78, 115, 223, 0.3);
        }

        .character-count {
            font-size: 0.8rem;
            text-align: right;
            color: #6c757d;
        }

        .animated-check {
            display: none;
            font-size: 4rem;
            color: var(--success-color);
            animation: scaleCheck 0.5s ease;
        }

        .media-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .media-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .media-preview-item img,
        .media-preview-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            border: none;
        }

        .file-input-label {
            display: block;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
        }

        .file-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        @keyframes scaleCheck {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            70% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .star {
                font-size: 2rem;
            }

            .rating-emoji {
                font-size: 1.8rem;
            }

            .card-header h3 {
                font-size: 1.5rem;
            }

            .btn-primary {
                width: 100%;
            }

            .media-preview-item {
                width: 80px;
                height: 80px;
            }
        }

        @media (max-width: 576px) {
            .star {
                font-size: 1.8rem;
                margin: 0 0.15rem;
            }

            .rating-card {
                margin: 0.5rem;
            }
        }
    </style>
    <div class="rating-card card card-flush">
        <div class="card-header">
            <h3 class="card-title">Beri Rating untuk Produk</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert"
                style="display: none !important;">
                <div class="animated-check">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="ms-3">
                    <h5 class="alert-heading mb-1">Terima Kasih!</h5>
                    <p class="mb-0">Rating berhasil disimpan!</p>
                </div>
            </div>
            <div class="mb-3 text-center">
                <div class="rating-emoji" id="ratingEmoji">😊</div>
                <label class="form-label fw-semibold fs-5">Seberapa puas Anda dengan produk ini?</label>
                <div class="star-rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fs-1 mx-1 cursor-pointer {{ $i <= $rating ? 'text-warning ki-solid ki-star' : 'text-gray-400 ki-filled ki-star star' }}"
                            data-value="{{ $i }}" wire:click="$set('rating', {{ $i }})"></i>
                    @endfor
                </div>
                <div class="text-muted small mt-2">Klik pada bintang untuk memberikan rating</div>
            </div>
            <div class="mb-3">
                <x-form-group name="review" label="Review">
                    <x-form-textarea name="review" class="bg-transparent" id="review" />
                    <div class="character-count mt-1">
                        <span id="charCount">0</span>/1000 karakter
                    </div>
                </x-form-group>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Tambahkan Media (Opsional, max 5 file)</label>

                <div class="position-relative d-inline-block">
                    <label class="file-input-label">
                        <i class="bi bi-cloud-upload me-2"></i>Pilih File
                        <input type="file" wire:model="media" multiple class="d-none"
                            accept="image/*,video/mp4,video/webm">
                    </label>
                    @if(count($media) > 0)
                        <span class="file-count-badge">{{ count($media) }}/5</span>
                    @endif
                </div>

                <small class="text-muted d-block mt-2">Upload gambar (JPG/PNG/GIF) atau video (MP4/WEBM), max
                    5MB/file.</small>

                @error('media')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('media.*')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Preview Media -->
            @if(count($media) > 0)
                <div class="media-preview-container mb-4">
                    @foreach($media as $index => $file)
                        <div class="media-preview-item">
                            @if(in_array($file->getMimeType(), ['video/mp4', 'video/webm']))
                                <video src="{{ $file->temporaryUrl() }}" controls></video>
                            @else
                                <img src="{{ $file->temporaryUrl() }}" alt="Preview">
                            @endif
                            <button class="media-remove-btn" wire:click="removeMedia({{ $index }})">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="form-check mb-5">
                <input class="form-check-input" type="checkbox" wire:model="is_anonymous" id="is_anonymous">
                <label class="form-check-label" for="is_anonymous">
                    Kirim sebagai anonim
                </label>
            </div>
            <x-button class="btn btn-primary rounded-pill fw-bold py-3 flex-grow-1" id="submitRating" submit="true"
                indicator="Harap tunggu..." label="Kirim Ulasan" />
        </div>
    </div>
    @section('custom_js')
        <script data-navigate-once>
            document.addEventListener('DOMContentLoaded', function () {
                const stars = document.querySelectorAll('.star');
                const ratingEmoji = document.getElementById('ratingEmoji');
                const reviewTextarea = document.getElementById('review');
                const charCount = document.getElementById('charCount');
                const anonymousSwitch = document.getElementById('anonymousSwitch');
                const submitButton = document.getElementById('submitRating');

                let currentRating = 0;

                // Star rating interaction
                stars.forEach(star => {
                    star.addEventListener('click', () => {
                        const value = parseInt(star.getAttribute('data-value'));
                        currentRating = value;

                        // Update emoji based on rating
                        switch (value) {
                            case 1:
                                ratingEmoji.textContent = '😠';
                                break;
                            case 2:
                                ratingEmoji.textContent = '😕';
                                break;
                            case 3:
                                ratingEmoji.textContent = '😐';
                                break;
                            case 4:
                                ratingEmoji.textContent = '😊';
                                break;
                            case 5:
                                ratingEmoji.textContent = '😍';
                                break;
                            default:
                                ratingEmoji.textContent = '😊';
                        }
                    });

                    // Hover effects for stars
                    star.addEventListener('mouseenter', (e) => {
                        const value = parseInt(e.target.getAttribute('data-value'));

                        stars.forEach(s => {
                            const starValue = parseInt(s.getAttribute('data-value'));
                            if (starValue <= value) {
                                s.classList.add('text-warning');
                            } else {
                                s.classList.remove('text-warning');
                            }
                        });
                    });

                    star.addEventListener('mouseleave', () => {
                        stars.forEach(s => {
                            s.classList.remove('text-warning');

                            // Restore active stars after hover
                            const starValue = parseInt(s.getAttribute('data-value'));
                            if (currentRating > 0 && starValue <= currentRating) {
                                s.classList.add('text-warning');
                            }
                        });
                    });
                });

                // Character count for review textarea
                reviewTextarea.addEventListener('input', () => {
                    const length = reviewTextarea.value.length;
                    charCount.textContent = length;

                    // Change color when approaching limit
                    if (length > 900) {
                        charCount.classList.add('text-danger');
                    } else {
                        charCount.classList.remove('text-danger');
                    }

                    // Enforce character limit
                    if (length > 1000) {
                        reviewTextarea.value = reviewTextarea.value.substring(0, 1000);
                        charCount.textContent = 1000;
                    }
                });
            });
        </script>
    @endsection
</x-form>