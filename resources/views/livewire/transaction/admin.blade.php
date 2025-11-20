<?php
use Carbon\Carbon;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Master\Branch;
use App\Models\Transaction\Rent;
use App\Models\Transaction\Sale;
use App\Services\MidtransService;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction\Payment;
use App\Models\Transaction\RentItem;
use function Livewire\Volt\{state, computed, usesPagination};

usesPagination(theme: 'bootstrap');

state(['search' => '', 'type' => '', 'status' => '', 'branch_id' => null]);
state([
    'selectedTransaction' => null,
    'jumlah_hari' => 1
]);
$openExtensionModal = function ($transactionId) {
    $this->selectedTransaction = $transactionId;
};
$transactions = computed(function () {
    $commonSelect = [
        'id',
        'code',
        'user_id',
        'branch_id',
        DB::raw('IFNULL(start_date, sale_date) as date_start'),
        DB::raw('end_date as date_end'),
        DB::raw('pickup_time as time_start'),
        'notes',
        DB::raw('0 as total_hour_late'), // Placeholder, hitung di method jika perlu
        DB::raw('IFNULL((SELECT value FROM promos WHERE id = rents.promo_id), 0) as discount_amount'),
        'deposit_amount',
        DB::raw('IFNULL(pickup_signature, receipt_number) as proof'),
        DB::raw('IFNULL(total_amount, total_amount) as total_amount'),
        DB::raw('IFNULL(paid_amount, paid_amount) as paid_amount'),
        DB::raw("DATEDIFF(rents.end_date, rents.start_date) as total_days"),
        'status',
        'created_at',
        'updated_at',
        DB::raw("type"),
    ];

    $rents = Rent::query()
        ->select([
            'id',
            'code',
            'user_id',
            'branch_id',
            'start_date',
            'end_date',
            'pickup_time',
            'notes',
            'deposit_amount',
            'pickup_signature',
            'total_amount',
            'paid_amount',
            'status',
            'created_at',
            'updated_at',
            'promo_id'
        ])
        ->with(['branch:id,name', 'rentItems.productBranch.product:id,name', 'promo:id,value,type'])
        ->whereNull('deleted_at')
        ->when($this->type === '' || $this->type === 'rent', fn($q) => $q)
        ->when($this->search, function ($query) {
            $query->where('code', 'like', '%' . $this->search . '%')
                ->orWhereHas('rentItems.productBranch.product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        })
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        ->when($this->branch_id, function ($query) {
            $query->where('branch_id', $this->branch_id);
        })
        ->addSelect(DB::raw("'rent' as type"));

    $sales = Sale::query()
        ->select([
            'id',
            'code',
            'user_id',
            'branch_id',
            'sale_date',
            'total_amount',
            'paid_amount',
            'receipt_number',
            'status',
            'created_at',
            'updated_at'
        ])
        ->with(['branch:id,name', 'saleItems.productBranch.product:id,name'])
        ->whereNull('deleted_at')
        ->when($this->type === '' || $this->type === 'sale', fn($q) => $q)
        ->when($this->search, function ($query) {
            $query->where('code', 'like', '%' . $this->search . '%')
                ->orWhereHas('saleItems.productBranch.product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        })
        ->when($this->status, function ($query) {
            $query->where('status', $this->status);
        })
        ->when($this->branch_id, function ($query) {
            $query->where('branch_id', $this->branch_id);
        })
        ->addSelect(DB::raw("'sale' as type"))
        ->addSelect(DB::raw('NULL as end_date, NULL as pickup_time, 0 as deposit_amount, NULL as pickup_signature, NULL as promo_id'));

    return $rents->union($sales)->latest()->paginate(10);
});

$branches = computed(function () {
    return Branch::select('id', 'name')->get();
});

$statusMap = computed(function () {
    return [
        'pending' => ['class' => 'badge-light-warning', 'text' => 'Menunggu'],
        'confirmed' => ['class' => 'badge-light-success', 'text' => 'Pembayaran Diterima'],
        'active' => ['class' => 'badge-light-success', 'text' => 'Sedang Berjalan'],
        'completed' => ['class' => 'badge-light-success', 'text' => 'Selesai'],
        'cancelled' => ['class' => 'badge-light-dark', 'text' => 'Dibatalkan'],
        'overdue' => ['class' => 'badge-light-warning', 'text' => 'Terlambat'],
        'paid' => ['class' => 'badge-light-success', 'text' => 'Dibayar'],
        'shipped' => ['class' => 'badge-light-primary', 'text' => 'Dikirim'],
    ];
});

// Method publik untuk batalkan
$batalkan = function ($transaction, $type) {
    if ($type == "rent") {
        $transaksi = Rent::findOrFail($transaction);
    } else {
        $transaksi = Sale::findOrFail($transaction);
    }
    if ($transaksi->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }
    // Logika untuk membatalkan transaksi
    // Misalnya, mengupdate status transaksi menjadi 'cancelled'
    $transaksi->castAndUpdate(['status' => 'cancelled']);
    $this->dispatch('toast-success', message: "Transaksi telah dibatalkan");
    $this->refreshPage();
};
$bayar = function ($transactionId) {
    try {
        $rent = Rent::findOrFail($transactionId);
        $midtransService = app(MidtransService::class);
        $paid_amount = $rent->payments->last()->payment_data['paid_amount'];
        // $snapToken = $rent->payments->last()->snap_token ?? $midtransService->createSnapToken($rent, $paid_amount, false);
        $snapToken = $rent->payments->last()->snap_token;
        $this->dispatch('show-snap', [
            'token' => $snapToken,
            'rentCode' => $rent->code
        ]);
    } catch (\Exception $e) {
        $this->dispatch('error', $e->getMessage());
    }
};
$pelunasan = function ($transactionId) {
    try {
        $rent = Rent::findOrFail($transactionId);
        $midtransService = app(MidtransService::class);
        $snapToken = $midtransService->createPelunasanToken($rent);
        $this->dispatch('show-snap', [
            'token' => $snapToken,
            'rentCode' => $rent->code
        ]);
    } catch (\Exception $e) {
        $this->dispatch('error', $e->getMessage());
    }
};
// Method publik untuk perpanjang sewa
$perpanjang = function ($transactionId) {
    $hari = $this->jumlah_hari;
    DB::beginTransaction();
    try {
        $rent = Rent::findOrFail($transactionId);
        $rent->status = 'completed';
        $rent->save();

        // Periksa ketersediaan produk
        $newEndDate = Carbon::parse($rent->end_date)->addDays($hari);
        foreach ($rent->items as $item) {
            if (
                !$item->productBranch->isAvailable(
                    $rent->end_date,
                    $newEndDate,
                    $item->quantity,
                    $rent->id // kecualikan transaksi saat ini
                )
            ) {
                throw new \Exception('Produk tidak tersedia untuk perpanjangan');
            }
        }
        $serviceFee = $rent->items->sum('subtotal') * 0.8 / 100; // 0.8% service fee
        $total = $rent->items->sum('subtotal') + $serviceFee;
        $newRent = Rent::create([
            'user_id' => auth()->id(),
            'branch_id' => $rent->branch_id,
            'status' => 'pending',
            'start_date' => $rent->end_date,
            'end_date' => $newEndDate,
            'pickup_time' => $rent->pickup_time,
            'deposit_amount' => $rent->deposit_amount,
            'ematerai_fee' => 0,
            'total_amount' => $total,
            'notes' => $rent->catatan,
        ]);
        foreach ($rent->items as $item) {
            RentItem::create([
                'rent_id' => $newRent->id,
                'product_branch_id' => $item->product_branch_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'duration_days' => $item->duration_days,
                'subtotal' => $item->subtotal,
            ]);
        }
        $midtransService = app(MidtransService::class);
        $orderId = 'EXT-' . $rent->code;
        // dd($newRent);
        $snapToken = $midtransService->createExtendToken($newRent, $total, false);
        if (!$snapToken) {
            throw new Exception('Failed to generate snap token');
        }
        $paymentData = [
            'paid_amount' => $total, // Default full payment
            'remaining_amount' => $total,
            'deposit_amount' => $newRent->deposit_amount ?? 0,
            'service_fee' => $serviceFee, // 0.8% service fee
            'ematerai_fee' => 0,
        ];
        $payment = Payment::castAndCreate([
            'payable_type' => get_class($newRent),
            'payable_id' => $newRent->id,
            'user_id' => Auth::id(),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID', 'default_merchant'),
            'order_id' => $midtransService->generateOrderId($newRent),
            'gross_amount' => $newRent->total_amount,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
            'transaction_time' => now(),
            'payment_data' => json_encode($paymentData),
            'snap_token' => $snapToken
        ]);
        DB::commit();

        $this->dispatch('show-snap-perpanjang', [
            'token' => $snapToken,
            'rentCode' => $newRent->code
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        $this->dispatch('error', $e->getMessage());
    }
};
$decreaseHari = function () {
    $this->jumlah_hari = $this->jumlah_hari - 1;
};
$increaseHari = function () {
    $this->jumlah_hari = $this->jumlah_hari + 1;
};
$refreshPage = function () {
    $this->redirect(route('transaction'), navigate: true);
};
$selesai = function ($id, $type) {
    if ($type == "rent") {
        $transaction = Rent::findOrFail($id);
    } else {
        $transaction = Sale::findOrFail($id);
    }
    if ($transaction->status != "active") {
        $this->dispatch('error', 'Transaksi tidak dalam status aktif.');
        return;
    }
    $transaction->status = 'completed';
    $transaction->save();
    $this->dispatch('toast-success', message: "Transaksi telah selesai");
    $this->refreshPage();
}
?>
<div>
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush mb-7">
                <div class="card-body pt-5">
                    <div class="row mt-3 mb-1">
                        <div class="col-12">
                            <h2 class="h3 fw-bold">Transaksi Saya</h2>
                            <p class="text-muted">Lacak sewa dan pembelian Anda dengan mudah</p>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md-6 col-lg-3 mb-3">
                            <input type="search" wire:model.live="search" class="form-control"
                                placeholder="Cari transaksi..." aria-label="Search transactions">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-3">
                            <select wire:model.live="type" class="form-select">
                                <option value="">Semua Transaksi</option>
                                <option value="rent">Sewa</option>
                                <option value="sale">Pembelian</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-3">
                            <select wire:model.live="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu</option>
                                <option value="confirmed">Pembayaran Diterima</option>
                                <option value="active">Sedang Berjalan</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                                <option value="overdue">Terlambat</option>
                                <option value="paid">Dibayar</option>
                                <option value="shipped">Dikirim</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-3">
                            <select wire:model.live="branch_id" class="form-select">
                                <option value="">Semua Cabang</option>
                                @foreach($this->branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-5">
                @forelse($this->transactions as $transaction)
                    @php
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

                        $statusInfo = $transaction->type === 'rent'
                            ? ($rentStatusMap[$transaction->status] ?? ['class' => 'badge-light-secondary', 'text' => ucfirst($transaction->status)])
                            : ($saleStatusMap[$transaction->status] ?? ['class' => 'badge-light-secondary', 'text' => ucfirst($transaction->status)]);
                    @endphp

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card card-dashed shadow-sm h-100 transition-all hover-shadow-lg rounded-3">

                            <!-- Header -->
                            <div class="card-header d-flex justify-content-between align-items-start p-3 p-md-4 border-0">
                                <div>
                                    <span
                                        class="badge bg-{{ $transaction->type === 'rent' ? 'info' : 'success' }} bg-opacity-10 text-{{ $transaction->type === 'rent' ? 'info' : 'success' }} mb-2">
                                        {{ $transaction->type === 'rent' ? 'Sewa' : 'Belanja' }}
                                    </span>
                                    <div class="text-muted fs-7 mb-1">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ $transaction->start_date->format('d M y') }} -
                                        {{ $transaction->end_date->format('d M y') }}
                                        <span class="d-inline-block d-md-none">({{ $transaction->total_days }} Hari)</span>
                                    </div>
                                    <a href="{{ route('user.show', ['user' => $transaction->user]) }}" wire:navigate
                                        class="fw-semibold text-gray-800 fs-7">
                                        <i class="ki-filled ki-user me-1"></i> Konsumen:
                                        {{ $transaction->user->name ?? 'N/A' }}
                                    </a>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $statusInfo['class'] }} fw-semibold px-3 py-2">
                                        {{ $statusInfo['text'] }}
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-light" type="button"
                                            data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($transaction->type == "rent" && $transaction->hasBeenSigned())
                                                <li><a class="dropdown-item"
                                                        href="{{ route('transaction.sign', ['code' => $transaction->code]) }}"
                                                        wire:navigate>Lihat Surat Perjanjian</a></li>
                                            @endif
                                            @if($transaction->type == "rent" ? $transaction->paymentRent : $transaction->paymentSale)
                                                <li><a class="dropdown-item"
                                                        href="{{ route('transaction.invoice', ['code' => $transaction->code]) }}"
                                                        wire:navigate>Cetak Invoice</a></li>
                                            @endif
                                            <li><a class="dropdown-item"
                                                    href="{{ route('transaction.view', ['code' => $transaction->code]) }}"
                                                    wire:navigate>Detail</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="card-body p-3 p-md-4">
                                @foreach ($transaction->items as $item)
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom last-border-0">
                                        <img src="{{ asset('storage/product/' . $item->productBranch->product->thumbnail) }}"
                                            alt="{{ $item->productBranch->product->name }}" 
                                            style="width: 45%; height: 45%">
                                        <div class="flex-grow-1 min-w-0">
                                            <h4 class="fw-bold fs-6 text-truncate mb-1"
                                                title="{{ $item->productBranch->product->name }}">
                                                {{ $item->productBranch->product->name }}
                                            </h4>
                                            <div class="text-muted fs-7">
                                                <i class="bi bi-tag-fill me-1"></i>
                                                Rp{{ number_format($transaction->type == "rent" ? $item->productBranch->rent_price : $item->productBranch->sale_price) }}
                                                {{ $transaction->type == "rent" ? '/Hari' : '' }}
                                                @if($transaction->type == "rent")
                                                    <span class="ms-2 ">x {{ $item->duration_days }} hari</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <p class="mb-0 text-muted fs-7">
                                    <i class="ki-filled ki-notepad me-1"></i>
                                    <strong>Catatan:</strong> {{ $transaction->notes ?? 'Tidak ada' }}
                                </p>
                            </div>

                            <!-- Footer -->
                            <div class="card-footer bg-light p-3 p-md-4 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fs-7 d-block">Total Harga</span>
                                        <span class="text-primary fw-bold fs-5">Rp
                                            {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        @if($transaction->type === 'rent')
                                            @if($transaction->status === 'confirmed')
                                                <a wire:navigate
                                                    href="{{ route('transaction.sign', ['code' => $transaction->code]) }}"
                                                    class="btn btn-light-primary btn-sm">
                                                    <i class="ki-filled ki-notepad-edit me-1"></i> Tanda Tangan
                                                </a>
                                            @elseif($transaction->status === 'active')
                                                <a wire:click="selesai({{ $transaction->id }}, '{{ $transaction->type }}')"
                                                    class="btn btn-light-success btn-sm">
                                                    <i class="ki-filled ki-check me-1"></i> Selesai
                                                </a>
                                            @elseif($transaction->status === 'completed' && $transaction->rating)
                                                <a wire:navigate
                                                    href="{{ route('transaction.rate', ['code' => $transaction->code]) }}"
                                                    class="btn btn-light-warning btn-sm">
                                                    <i class="ki-filled ki-star me-1"></i> Lihat Ulasan
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('transaction.invoice', ['code' => $transaction->code]) }}"
                                                wire:navigate class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-receipt me-1"></i> Lihat Invoice
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center">
                        <p class="text-muted">Tidak ada transaksi ditemukan.</p>
                    </div>
                @endforelse
            </div>

            @if($this->transactions->hasPages())
                <div class="row mt-4 mb-10">
                    <div class="col-12">
                        {{ $this->transactions->links() }}
                    </div>
                </div>
            @endif
            <div class="modal fade" id="extensionModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Perpanjang Sewa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold me-4">
                                    Jumlah Hari :
                                </label>
                                <div class="position-relative w-md-100px" data-kt-dialer="true" data-kt-dialer-min="1"
                                    data-kt-dialer-max="365" data-kt-dialer-step="1" data-kt-dialer-prefix=""
                                    data-kt-dialer-decimals="0">
                                    <button type="button" wire:click="decreaseHari"
                                        class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0"
                                        data-kt-dialer-control="decrease">
                                        <i class="ki-filled ki-minus-squared fs-2"></i>
                                    </button>
                                    <input type="text" wire:model.live="jumlah_hari"
                                        class="form-control form-control-solid border-0 ps-12"
                                        data-kt-dialer-control="input" placeholder="Amount" readonly />
                                    <button type="button" wire:click="increaseHari"
                                        class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0"
                                        data-kt-dialer-control="increase">
                                        <i class="ki-filled ki-plus-squared fs-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-primary"
                                wire:click="perpanjang({{ $selectedTransaction }})" data-bs-dismiss="modal">
                                Lanjutkan Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @section('custom_js')
                <script data-navigate-once src="https://app.sandbox.midtrans.com/snap/snap.js"
                    data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
                <script data-navigate-once>
                    function showSnap(payload) {
                        // Validasi payload
                        if (!payload) {
                            console.error('Payload is missing');
                            return;
                        }

                        const { token, rentCode } = payload;

                        if (!token) {
                            console.error('Snap token is missing in payload', payload);
                            return;
                        }

                        // Pastikan snap telah terinisialisasi
                        if (typeof snap === 'undefined') {
                            console.error('Snap.js belum dimuat');
                            return;
                        }

                        // Jalankan pembayaran Snap
                        snap.pay(token, {
                            onSuccess: function (result) {
                                Swal.fire({
                                    title: 'Pembayaran Berhasil!',
                                    text: 'Pelunasan transaksi telah berhasil.',
                                    icon: 'success',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(() => {
                                    window.location.href = `/consumer/transaction/${rentCode}/view`;
                                });
                            },
                            onPending: function (result) {
                                Swal.fire({
                                    title: 'Pembayaran Tertunda',
                                    text: 'Silakan selesaikan pembayaran Anda.',
                                    icon: 'info',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(() => {
                                    window.location.href = `/consumer/transaction/${rentCode}/view`;
                                });
                            },
                            onError: function (error) {
                                Swal.fire({
                                    title: 'Pembayaran Gagal',
                                    text: 'Terjadi kesalahan saat memproses pembayaran.',
                                    icon: 'error'
                                });
                            },
                            onClose: function () {
                                Swal.fire({
                                    title: 'Pembayaran Dibatalkan',
                                    text: 'Anda menutup jendela pembayaran.',
                                    icon: 'warning'
                                });
                            }
                        });
                    }
                    function showSnapPerpanjang(payload) {
                        // Validasi payload
                        if (!payload) {
                            console.error('Payload is missing');
                            return;
                        }

                        const { token, rentCode } = payload;

                        if (!token) {
                            console.error('Snap token is missing in payload', payload);
                            return;
                        }

                        // Pastikan snap telah terinisialisasi
                        if (typeof snap === 'undefined') {
                            console.error('Snap.js belum dimuat');
                            return;
                        }
                        snap.pay(token, {
                            onSuccess: function (result) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Perpanjangan sewa berhasil',
                                    icon: 'success',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(() => {
                                    @this.refreshPage();
                                });
                            },
                            onPending: function (result) {
                                Swal.fire({
                                    title: 'Pembayaran Tertunda',
                                    text: 'Silakan selesaikan pembayaran Anda.',
                                    icon: 'info',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(() => {
                                    window.location.href = `/consumer/transaction/${rentCode}/view`;
                                });
                            },
                            onError: function (error) {
                                Swal.fire({
                                    title: 'Pembayaran Gagal',
                                    text: 'Terjadi kesalahan saat memproses pembayaran.',
                                    icon: 'error'
                                });
                            },
                            onClose: function () {
                                Swal.fire({
                                    title: 'Pembayaran Dibatalkan',
                                    text: 'Anda menutup jendela pembayaran.',
                                    icon: 'warning'
                                });
                            }
                        });
                    }
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
                    document.addEventListener('DOMContentLoaded', () => {
                        window.addEventListener('show-snap', (event) => {
                            showSnap(event.detail[0]);
                        });
                        window.addEventListener('show-snap-perpanjang', (event) => {
                            showSnapPerpanjang(event.detail[0]);
                        });
                    });
                    document.addEventListener('livewire:navigated', () => {
                        window.addEventListener('show-snap', (event) => {
                            showSnap(event.detail[0]);
                        });
                        window.addEventListener('show-snap-perpanjang', (event) => {
                            showSnapPerpanjang(event.detail[0]);
                        });
                    });
                </script>
            @endsection
        </div>
    </div>
</div>