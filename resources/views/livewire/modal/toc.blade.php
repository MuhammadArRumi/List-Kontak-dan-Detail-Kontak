<?php

use function Livewire\Volt\{computed};
$collection = computed(function() {
    return [
        [
            'title' => '1. Definisi Umum',
            'content' => "Platform: Adamasanya termasuk aplikasi & situs web.\nPengguna: Semua pihak yang menggunakan layanan.\nPenyewa: Pengguna yang menyewa produk elektronik dari platform Adamasanya.\nAdamasanya: Pihak penyedia layanan yang menyediakan sarana sewa dan jual beli elektronik."
        ],
        [
            'title' => '2. Ketentuan Akun dan Pendaftaran',
            'content' => "Pengguna wajib mengisi data dengan benar...\nVerifikasi email wajib dilakukan...\nKonsumen wajib melengkapi profil secara menyeluruh, termasuk data pribadi, identitas (KTP, selfie, dan KK), serta akun media sosial."
        ],
        [
            'title' => '3. Kebijakan Verifikasi Konsumen',
            'content' => "Konsumen yang telah mengisi data lengkap akan menjalani proses verifikasi oleh sistem dan/atau admin.\nAdmin dapat menggunakan sistem AI (pencocokan wajah, validasi data, analisis sosial) untuk mempercepat proses validasi.\nKonsumen wajib memastikan data yang diunggah valid, asli, dan milik sendiri."
        ],
        [
            'title' => '4. Kebijakan iCloud',
            'content' => "Seluruh perangkat Apple disewakan dengan akun iCloud milik Adamasanya.\nPenyewa tidak diperbolehkan meminta kata sandi iCloud atau mengubah pengaturan iCloud.\nPelanggaran terhadap kebijakan ini akan dianggap sebagai pelanggaran berat dan berpotensi tindak pidana."
        ],
        [
            'title' => '5. Ketentuan Domisili',
            'content' => "Jika domisili penyewa tidak sesuai dengan cabang Adamasanya tempat penyewaan, maka wajib memberikan deposit sesuai unit sewa yang berlaku."
        ],
        [
            'title' => '6. Ketentuan Pembatalan Sewa',
            'content' => "Pembatalan -> Tidak ada refund."
        ],
        [
            'title' => '7. Keterlambatan Pengembalian',
            'content' => "Denda Rp10.000 per jam keterlambatan.\nJika keterlambatan melebihi 6 jam → Dihitung sebagai 1 hari sewa penuh.\nJika unit tidak dibayar/dikembalikan dalam 3 hari → Akan dilakukan tindakan hukum dan penyebaran informasi kepada publik (siap diviralkan)."
        ],
        [
            'title' => '8. Tanggung Jawab atas Barang Sewa',
            'content' => "Penyewa bertanggung jawab penuh atas unit yang disewa.\nJika unit hilang atau rusak → Menjadi tanggung jawab penyewa secara penuh.\nWajib memastikan data pribadi sudah dipindahkan dan akun sosmed telah dikeluarkan sebelum pengembalian. Jika tidak, Adamasanya tidak bertanggung jawab atas kehilangan data."
        ],
        [
            'title' => '9. Jaminan Identitas',
            'content' => "Penyewa wajib menyerahkan jaminan berupa KTP/SIM/KIA/Kartu Pelajar selama periode penyewaan."
        ],
        [
            'title' => '10. Hak Kekayaan Intelektual',
            'content' => "Seluruh konten (teks, gambar, logo, data sistem) adalah milik Adamasanya dan dilindungi undang-undang.\nDilarang menyalin, mendistribusikan, atau memanfaatkan konten tanpa izin tertulis."
        ],
        [
            'title' => '11. Larangan Penggunaan',
            'content' => "Pengguna dilarang:\nMenggunakan platform untuk tindakan ilegal.\nMenipu, menyamar sebagai pihak lain, atau menyalahgunakan sistem.\nMengunggah konten yang mengandung SARA, pornografi, atau spam."
        ],
        [
            'title' => '12. Pemutusan Akses',
            'content' => "Adamasanya berhak memutuskan akses pengguna secara sementara atau permanen apabila:\nMelanggar ketentuan ini.\nMelakukan tindakan merugikan secara hukum atau operasional."
        ],
        [
            'title' => '13. Hukum yang Berlaku',
            'content' => "Syarat dan Ketentuan ini tunduk pada hukum yang berlaku di Republik Indonesia. Sengketa akan diselesaikan melalui musyawarah atau pengadilan negeri Bandung."
        ],
        [
            'title' => '14. Perubahan Syarat & Ketentuan',
            'content' => "Kami berhak memperbarui syarat dan ketentuan ini kapan saja. Perubahan akan diinformasikan melalui platform atau email resmi pengguna."
        ],
        [
            'title' => '15. Kontak',
            'content' => "Jika Anda memiliki pertanyaan, silakan hubungi kami melalui adamasanyaforlife@gmail.com / +62 877-6534-6368."
        ],
    ];
});
?>
<div class="modal fade" id="ModalSnK" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-xl">
        <!--begin::Modal content-->
        <div class="modal-content rounded">
            <!--begin::Modal header-->
            <div class="modal-header justify-content-end border-0 pb-0">
                <!--begin::Close-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body pt-0 pb-15 px-5 px-xl-20">
                <div class="d-flex flex-column flex-lg-row">
                    <!--begin::Sidebar-->
                    <div class="flex-column flex-lg-row-auto w-100 w-lg-275px mb-10 me-lg-20 d-none d-lg-block">
                        <ul class="nav nav-tabs nav-pills flex-row border-0 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px">
                            @foreach($this->collection as $index => $section)
                            <li class="nav-item w-100 me-0 mb-md-2">
                                <a class="nav-link w-100 {{ $index === 0 ? 'active' : '' }} btn btn-flex btn-active-light-success text-start" 
                                data-bs-toggle="tab" 
                                href="#terms_{{ $index }}">
                                    <span class="d-flex flex-column align-items-start">
                                        <span class="fs-4 fw-bold text-start">{{ $section['title'] }}</span>
                                    </span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <!--end::Sidebar-->
                    <!--begin::Content-->
                    <div class="flex-lg-row-fluid">
                        <!--begin::Extended content-->
                        <div class="mb-13">
                            <!--begin::Content-->
                            <div class="mb-15">
                                <!--begin::Title-->
                                <h4 class="fs-2x text-gray-800 w-bolder mb-6">Syarat dan Ketentuan Penggunaan (Terms and Conditions of Use) – Adamasanya</h4>
                                <!--end::Title-->
                                <!--begin::Text-->
                                <p class="fw-semibold fs-4 text-gray-600 mb-2">Tanggal Berlaku: <strong>1 Mei 2025</strong></p>
                                <p class="fw-semibold fs-4 text-gray-600 mb-2">
                                    Selamat datang di Adamasanya. Dengan mengakses, mendaftar, atau menggunakan platform kami baik melalui situs web maupun aplikasi, Anda menyetujui untuk terikat oleh syarat dan ketentuan ini. Jika Anda tidak menyetujui salah satu bagian dari dokumen ini, Anda tidak diperbolehkan untuk menggunakan layanan kami.
                                </p>
                                <!--end::Text-->
                            </div>
                            <!--end::Content-->
                            <!--begin::Item-->
                            <div class="mb-15 d-none d-lg-block">
                                <div class="tab-content" id="termsContent">
                                    @foreach($this->collection as $index => $section)
                                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="terms_{{ $index }}" role="tabpanel">
                                        <h3 class="text-gray-800 w-bolder mb-4">{{ $section['title'] }}</h3>
                                        <div class="mb-4 text-gray-600 fw-semibold fs-6 ps-10">
                                            {!! nl2br(e($section['content'])) !!}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <!--begin::Accordion-->
                            <div class="accordion accordion-icon-collapse d-block d-md-none" id="termsAccordion">
                                @foreach($this->collection as $index => $section)
                                <!--begin::Item-->
                                <div class="mb-5">
                                    <!--begin::Header-->
                                    <div class="accordion-header py-3 d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#accordion{{$index}}">
                                        <span class="accordion-icon">
                                            <i class="ki-outline ki-plus-square fs-3 accordion-icon-off"></i>
                                            <i class="ki-outline ki-minus-square fs-3 accordion-icon-on"></i>
                                        </span>
                                        <h3 class="fs-4 fw-semibold mb-0 ms-4 text-gray-800">{{ $section['title'] }}</h3>
                                    </div>
                                    <!--end::Header-->

                                    <!--begin::Body-->
                                    <div id="accordion{{$index}}" class="fs-6 collapse ps-10" data-bs-parent="#termsAccordion">
                                        <div class="accordion-body py-4 ps-6 pe-0">
                                            {!! nl2br(e($section['content'])) !!}
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                </div>
                                <!--end::Item-->
                                @endforeach
                            </div>
                            <!--end::Accordion-->
                            <!--end::Item-->
                        </div>
                        <!--end::Extended content-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--begin::Actions-->
                <div class="d-flex flex-center flex-row-fluid pt-12">
                    <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Saya Tidak Setuju</button>
                    <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">
                        <!--begin::Indicator label-->
                        <span class="indicator-label">Saya Setuju</span>
                        <!--end::Indicator label-->
                        <!--begin::Indicator progress-->
                        <span class="indicator-progress">Harap Tunggu... 
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        <!--end::Indicator progress-->
                    </button>
                </div>
                <!--end::Actions-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>