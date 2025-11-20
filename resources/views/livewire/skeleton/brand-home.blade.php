<div class="mt-10 mb-10">
    <div class="row g-3 g-md-4">
        @for ($i = 0; $i < 6; $i++)
        <div class="col-4 col-sm-3 col-md-2 col-lg-2 col-xl-1-5">
            <div class="card card-category h-100 border-0 shadow-sm rounded-3 overflow-hidden placeholder-wave">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                    <div class="position-relative mb-3">
                        <div class="bg-light rounded-circle p-3 d-flex align-items-center justify-content-center" 
                            style="width: 80px; height: 80px;">
                            <div class="placeholder rounded-circle" style="width: 50px; height: 50px;"></div>
                        </div>
                    </div>
                    <div class="text-center w-100">
                        <h5 class="text-gray-800 fw-bold mb-0 fs-6 truncate-2 placeholder-wave">
                            <span class="placeholder col-10"></span>
                        </h5>
                        <span class="text-muted fs-7 mt-1 d-block placeholder-wave">
                            <span class="placeholder col-4"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endfor
    </div>
</div>