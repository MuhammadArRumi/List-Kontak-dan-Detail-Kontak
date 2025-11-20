<?php
use App\Models\Transaction\Rent;
use App\Models\Transaction\Sale;
use function Livewire\Volt\{state, computed};
state(['code' => null]);
$transaction = computed(function () {
    // Try to find the transaction in Rent model
    $rent = Rent::query()
    ->select(
        'rents.id',
        'rents.code',
        'rents.user_id',
        'rents.branch_id',
        'rents.promo_id',
        'rents.status',
        'rents.start_date',
        'rents.end_date',
        'rents.pickup_time',
        'rents.return_time',
        'rents.total_amount',
        'rents.deposit_amount',
        'rents.ematerai_fee',
        'rents.paid_amount',
        'rents.notes',
        'rents.pickup_signature',
        'rents.pickup_ematerai_id',
        'rents.return_signature',
        'rents.return_ematerai_id',
        'rents.created_at',
        'rents.updated_at',
        DB::raw("'rent' as type"),
        DB::raw("DATEDIFF(rents.end_date, rents.start_date) as total_days")
    )
    ->with(['branch', 'rentItems.productBranch', 'rentItems.productBranch.product', 'payments'])
    ->where('user_id', auth()->id())
    ->where('code', $this->code)
    ->first();

    // If rent not found, try Sale model
    if (!$rent) {
        $sale = Sale::query()
        ->select(
            'sales.id',
            'sales.code',
            'sales.user_id',
            'sales.branch_id',
            'sales.promo_id',
            'sales.sale_date',
            'sales.total_amount',
            'sales.paid_amount',
            'sales.receipt_number',
            'sales.shipping_price',
            'sales.payment_type',
            'sales.status',
            'sales.created_at',
            'sales.updated_at',
            DB::raw("'sale' as type"),
            DB::raw("0 as discount_amount") // Add this if you need discount amount
        )
        ->with(['branch', 'saleItems.productBranch', 'saleItems.productBranch.product', 'payments'])
        ->where('user_id', auth()->id())
        ->where('code', $this->code)
        ->first();
        
        return $sale;
    }

    return $rent;
});

$batalkan = function($id, $type) {
    if ($type === 'rent') {
        $transaksi = Rent::findOrFail($id);
    } else {
        $transaksi = Sale::findOrFail($id);
    }

    if ($transaksi->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }
    
    // Update status langsung
    $transaksi->update(['status' => 'cancelled']);
    
    $this->dispatch('toast-success', message: "Transaksi telah dibatalkan");
    $this->redirect(route('transaction'), navigate: true);
}
?>
<div>
    @if(!$this->transaction)
        <div class="d-flex flex-column align-items-center justify-content-center min-vh-50 py-10">
            <div class="text-center mb-6">
                <i class="bi bi-receipt text-muted" style="font-size: 5rem;"></i>
                <h3 class="mt-4">Transaksi Tidak Ditemukan</h3>
                <p class="text-muted">Kami tidak dapat menemukan transaksi dengan kode tersebut</p>
            </div>
            <a href="{{ route('transaction') }}" wire:navigate class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar Transaksi
            </a>
        </div>
    @else
        @php
            // Mapping manual untuk status
            $rentStatusMap = [
                'pending' => ['class' => 'badge-light-primary', 'text' => 'Menunggu Konfirmasi'],
                'confirmed' => ['class' => 'badge-light-success', 'text' => 'Pembayaran Diterima'],
                'active' => ['class' => 'badge-light-success', 'text' => 'Sedang Berjalan'],
                'completed' => ['class' => 'badge-light-success', 'text' => 'Selesai'],
                'cancelled' => ['class' => 'badge-light-dark', 'text' => 'Dibatalkan'],
                'overdue' => ['class' => 'badge-light-danger', 'text' => 'Jatuh Tempo'],
            ];

            $saleStatusMap = [
                'pending' => ['class' => 'badge-light-primary', 'text' => 'Menunggu Konfirmasi'],
                'confirmed' => ['class' => 'badge-light-success', 'text' => 'Dikonfirmasi'],
                'on_delivery' => ['class' => 'badge-light-info', 'text' => 'Dalam Pengiriman'],
                'completed' => ['class' => 'badge-light-success', 'text' => 'Selesai'],
                'cancelled' => ['class' => 'badge-light-dark', 'text' => 'Dibatalkan'],
            ];

            $statusInfo = $this->transaction->type === 'rent' 
                ? ($rentStatusMap[$this->transaction->status] ?? ['class' => 'badge-light-secondary', 'text' => $this->transaction->status])
                : ($saleStatusMap[$this->transaction->status] ?? ['class' => 'badge-light-secondary', 'text' => $this->transaction->status]);
        @endphp
        
        <div class="order-container max-w-3xl mx-auto px-3 sm:px-0">
            <!-- Status Header -->
            <div class="card mb-4 sm:mb-6 rounded-xl overflow-hidden">
                <div class="bg-{{ $statusInfo['class'] == 'badge-light-success' ? 'success' : 'primary' }}-subtle p-4 sm:p-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="fw-bold mb-1">Detail Transaksi</h2>
                            <p class="text-muted mb-0">Kode: {{ $this->transaction->code }}</p>
                        </div>
                        <span class="badge {{ $statusInfo['class'] }} fs-6 py-2 px-3">
                            {{ $statusInfo['text'] }}
                        </span>
                    </div>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-muted small">Tanggal Transaksi</div>
                            <div class="fw-medium">
                                {{ \Carbon\Carbon::parse($this->transaction->created_at)->translatedFormat('d F Y, H:i') }}
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="text-muted small">Invoice</div>
                            <a href="#" class="fw-medium text-primary text-decoration-none">
                                INV/{{ $this->transaction->code }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch & Products -->
            <div class="card mb-4 sm:mb-6">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                    <h3 class="card-title fs-5 mb-0">Produk</h3>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-2 d-none d-sm-inline">{{ $this->transaction->branch->name }}</span>
                        <i class="bi bi-shop fs-4 text-primary"></i>
                    </div>
                </div>
                
                <div class="card-body py-3">
                    @foreach($this->transaction->items as $item)
                        @php
                            $product = $item->productBranch->product;
                            $variant = $item->productBranch->variant;
                            $price = $item->price;
                            $quantity = $item->quantity;
                            $subtotal = $price * $quantity;
                        @endphp
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="product-img d-flex align-items-center justify-content-center">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="img-fluid">
                                    @else
                                        <i class="bi bi-box-seam text-muted" style="font-size: 28px;"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">{{ $product->name }}</h6>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="d-block text-muted small">Varian: {{ $variant }}</span>
                                        <span class="d-block mt-1">{{ $quantity }} x Rp{{ number_format($price, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="fw-bold">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Product List -->
                    <div class="d-grid">
                        <button class="btn btn-light btn-view-all d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-grid me-2"></i>Lihat Semua Barang ({{ $this->transaction->items->count() }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- Protection -->
            <div class="card mb-4 sm:mb-6">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Proteksi Transaksi</h6>
                            <p class="mb-0 text-muted small">Setelah pesanan selesai, kamu bisa cek polis asuransi</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-success bg-opacity-10 text-success py-2 px-3">
                            <i class="bi bi-check-circle-fill me-1"></i> Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipping Info / Rent Info -->
            @if($this->transaction->type === 'sale')
                <!-- Tampilan untuk Sale -->
                <div class="card mb-4 sm:mb-6">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                        <h3 class="card-title fs-5 mb-0">Info Pengiriman</h3>
                        <a href="#" class="btn btn-sm btn-light">Lihat Detail</a>
                    </div>
                    
                    <div class="card-body py-3">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="text-muted small">Kurir</div>
                                <div class="fw-medium">{{ $this->transaction->shipping_courier ?? 'Reguler' }}</div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="text-muted small">No. Resi</div>
                                <div class="fw-medium">{{ $this->transaction->receipt_number ?? '-' }}</div>
                            </div>
                        </div>
                        
                        <div class="border-start border-primary border-3 ps-3 mt-4">
                            <h6 class="mb-1 fw-bold">{{ auth()->user()->name }}</h6>
                            <p class="mb-1">{{ auth()->user()->phone }}</p>
                            <p class="mb-0 text-muted small">
                                {{ auth()->user()->address }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Tampilan untuk Rent -->
                <div class="card mb-4 sm:mb-6">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                        <h3 class="card-title fs-5 mb-0">Info Peminjaman</h3>
                    </div>
                    
                    <div class="card-body py-3">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="text-muted small">Tanggal Mulai</div>
                                <div class="fw-medium">
                                    {{ \Carbon\Carbon::parse($this->transaction->start_date)->translatedFormat('d F Y') }}
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="text-muted small">Tanggal Selesai</div>
                                <div class="fw-medium">
                                    {{ \Carbon\Carbon::parse($this->transaction->end_date)->translatedFormat('d F Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-muted small">Waktu Pengambilan</div>
                                <div class="fw-medium">{{ $this->transaction->pickup_time->format('H:i') }}</div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="text-muted small">Waktu Pengembalian</div>
                                <div class="fw-medium">{{ $this->transaction->return_time }}</div>
                            </div>
                        </div>
                        <div class="border-start border-primary border-3 ps-3 mt-4">
                            <h6 class="mb-1 fw-bold">Ambil di Cabang</h6>
                            <p class="mb-1">{{ $this->transaction->branch->name }}</p>
                            <p class="mb-0 text-muted small">
                                {{ $this->transaction->branch->address }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payment Details -->
            <div class="card mb-4 sm:mb-6">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                    <h3 class="card-title fs-5 mb-0">Rincian Pembayaran</h3>
                    <a href="#" class="btn btn-sm btn-light">Lihat Detail</a>
                </div>
                
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Metode Pembayaran</span>
                        <span class="fw-medium">
                            {{ $this->transaction->payment_type ?? 'BCA Virtual Account' }}
                        </span>
                    </div>
                    <!-- Tampilkan rincian berdasarkan jenis transaksi -->
                    @if($this->transaction->type === 'sale')
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal Produk</span>
                            <span>Rp{{ number_format($this->transaction->total_amount - $this->transaction->shipping_price, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Ongkos Kirim</span>
                            <span>Rp{{ number_format($this->transaction->shipping_price, 0, ',', '.') }}</span>
                        </div>
                    @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal Produk</span>
                            <span>Rp{{ number_format($this->transaction->items->sum('subtotal'), 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Deposit</span>
                            <span>Rp{{ number_format($this->transaction->deposit_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Biaya Materai</span>
                            <span>Rp{{ number_format($this->transaction->ematerai_fee, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Biaya Layanan</span>
                            <span>Rp{{ number_format($this->transaction->items->sum('subtotal')*0.8/100, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if(($this->transaction->discount_amount ?? 0) > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Diskon</span>
                            <span class="text-success">-Rp{{ number_format($this->transaction->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <h6 class="mb-0 fw-bold">Total Pembayaran</h6>
                            <p class="small text-muted mb-0">Belum termasuk biaya transaksi</p>
                        </div>
                        <div class="fs-5 fw-bold">Rp{{ number_format($this->transaction->total_amount, 0, ',', '.') }}</div>
                    </div>
                    
                    @if(($this->transaction->discount_amount ?? 0) > 0)
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 mt-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-tag-fill text-success fs-5 me-2"></i>
                                <div>Kamu dapat diskon Rp{{ number_format($this->transaction->discount_amount, 0, ',', '.') }} di transaksi ini</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
                <a href="{{ route('transaction') }}" wire:navigate class="btn btn-light flex-grow-1 py-3">
                    <i class="bi bi-arrow-left me-2"></i> Kembali
                </a>
                
                @if($this->transaction->status == 'pending' || $this->transaction->status == 'confirmed')
                <button onclick="batalkan({{ $this->transaction->id }}, '{{ $this->transaction->type }}')" 
                        class="btn btn-outline-danger flex-grow-1 py-3">
                    <i class="bi bi-x-circle me-2"></i> Batalkan Pesanan
                </button>
                @endif
            </div>

            <!-- Footer -->
            <div class="text-center text-muted small mb-4">
                <p class="mb-1">© 2025 Toko Online. Semua hak dilindungi.</p>
                <p class="mb-0">Butuh bantuan? <a href="#" class="text-decoration-none">Hubungi Kami</a></p>
            </div>
        </div>
    @endif
    @section('custom_js')
        <script data-navigate-once>
            function batalkan(id, type) {
                Swal.fire({
                    title: 'Batalkan transaksi ini?',
                    text: "Transaksi ini akan dibatalkan dan tidak dapat dikembalikan.",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tidak',
                    reverseButtons: true,
                    backdrop: true,
                    allowOutsideClick: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Membatalkan transaksi...',
                            html: 'Sedang memproses permintaan Anda',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                @this.batalkan(id, type).then(() => {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: 'Transaksi berhasil dibatalkan',
                                        icon: 'success',
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                }).catch(error => {
                                    Swal.fire({
                                        title: 'Gagal!',
                                        html: `Gagal membatalkan transaksi: <br><span class="text-red-500">${error.message}</span>`,
                                        icon: 'error'
                                    });
                                });
                            }
                        });
                    }
                });
            }
        </script>
    @endsection
</div>