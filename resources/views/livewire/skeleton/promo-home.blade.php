@auth
    <!-- Skeleton untuk promo section (saat loading) -->
    <div class="py-8 py-lg-12 bg-gradient-promo rounded-4 shadow-lg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-6 mb-lg-0 text-white">
                    <!-- Badge skeleton -->
                    <div class="skeleton mb-3 py-2 px-3 rounded-pill w-25"></div>
                    
                    <!-- Judul skeleton -->
                    <div class="skeleton mb-4 w-75" style="height: 50px;"></div>
                    
                    <!-- Deskripsi skeleton -->
                    <div class="skeleton mb-4 w-100" style="height: 24px;"></div>
                    <div class="skeleton mb-4 w-90" style="height: 24px;"></div>
                    <div class="skeleton mb-4 w-80" style="height: 24px;"></div>
                    
                    <!-- Kode promo skeleton -->
                    <div class="d-flex align-items-center mb-5">
                        <div class="skeleton me-2 w-25" style="height: 24px;"></div>
                        <div class="skeleton px-4 py-2 rounded-pill w-25" style="height: 40px;"></div>
                    </div>
                    
                    <!-- Tombol skeleton -->
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="skeleton btn-lg rounded-pill px-5 py-3 w-50" style="height: 50px;"></div>
                        <div class="d-flex align-items-center">
                            <div class="skeleton me-2 rounded-circle" style="width: 24px; height: 24px;"></div>
                            <div class="skeleton w-50" style="height: 24px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 position-relative">
                    <div class="position-relative text-center">
                        <!-- Gambar skeleton -->
                        <div class="skeleton w-75 mx-auto rounded-4" style="height: 250px;"></div>
                        
                        <!-- Hot deal badge skeleton -->
                        <div class="position-absolute top-0 end-0 skeleton fs-3 fw-bold px-4 py-2 rounded-3 shadow" 
                            style="transform: rotate(15deg); width: 120px; height: 40px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Countdown skeleton -->
            <div class="mt-8">
                <div class="bg-white bg-opacity-20 p-4 rounded-4">
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                        <div class="mb-3 mb-md-0">
                            <div class="skeleton fw-bold fs-3 mb-0 w-100" style="height: 36px;"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="text-center">
                                <div class="skeleton fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" style="height: 50px;"></div>
                                <div class="skeleton mt-1 d-block fs-6 w-100 mx-auto" style="height: 20px;"></div>
                            </div>
                            <div class="text-center">
                                <div class="skeleton fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" style="height: 50px;"></div>
                                <div class="skeleton mt-1 d-block fs-6 w-100 mx-auto" style="height: 20px;"></div>
                            </div>
                            <div class="text-center">
                                <div class="skeleton fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" style="height: 50px;"></div>
                                <div class="skeleton mt-1 d-block fs-6 w-100 mx-auto" style="height: 20px;"></div>
                            </div>
                            <div class="text-center">
                                <div class="skeleton fw-bold fs-3 px-3 py-2 rounded-3 countdown-box" style="height: 50px;"></div>
                                <div class="skeleton mt-1 d-block fs-6 w-100 mx-auto" style="height: 20px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Skeleton untuk guest section -->
    <div class="hero-section py-10 py-lg-15 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-8 mb-lg-0">
                    <!-- Judul skeleton -->
                    <div class="skeleton display-5 fw-bold mb-4 w-100" style="height: 60px;"></div>
                    
                    <!-- Deskripsi skeleton -->
                    <div class="skeleton fs-5 mb-6 w-100" style="height: 24px;"></div>
                    <div class="skeleton fs-5 mb-6 w-90" style="height: 24px;"></div>
                    
                    <!-- Tombol skeleton -->
                    <div class="d-flex flex-wrap gap-3">
                        <div class="skeleton btn-lg fw-bold px-5 py-3 rounded-pill w-50" style="height: 50px;"></div>
                        <div class="skeleton btn-lg px-5 py-3 rounded-pill w-40" style="height: 50px;"></div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <!-- Gambar skeleton -->
                    <div class="skeleton mx-auto rounded-4" style="width: 85%; height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
@endauth