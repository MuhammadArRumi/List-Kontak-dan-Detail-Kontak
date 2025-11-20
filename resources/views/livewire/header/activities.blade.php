<?php
use function Livewire\Volt\{state};
use Spatie\Activitylog\Models\Activity;
state([
    'activities' => fn() => Auth::check() ? Activity::with('causer')
            ->latest()
            ->take(20)
            ->get() : collect(),
]);
$loadActivities = function() {
    if (Auth::check()) {
        $this->activities = Activity::with('causer')
            ->latest()
            ->take(20)
            ->get();
    }
};
?>
<div id="kt_activities" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="activities" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'lg': '300px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_activities_toggle" data-kt-drawer-close="#kt_activities_close">
    <div class="card shadow-none border-0 rounded-0">
        <div class="card-header" id="kt_activities_header">
            <h3 class="card-title fw-bold text-gray-900">Activity Logs</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5" id="kt_activities_close">
                    <i class="ki-filled ki-cross fs-1"></i>
                </button>
            </div>
        </div>
        <div class="card-body position-relative" id="kt_activities_body">
            <div id="kt_activities_scroll" class="position-relative scroll-y me-n5 pe-5" data-kt-scroll="true" data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_activities_body" data-kt-scroll-dependencies="#kt_activities_header, #kt_activities_footer" data-kt-scroll-offset="5px">
                <div class="timeline">
                    @foreach($this->activities as $activity)
                        <div class="timeline-item">
                            <div class="timeline-line w-40px"></div>
                            <div class="timeline-icon">
                                <i class="ki-filled ki-{{ $activity->event ?? 'abstract-26' }} fs-2 text-gray-500"></i>
                            </div>
                            <div class="timeline-content mb-10 mt-n1">
                                <div class="pe-3 mb-5">
                                    <div class="fs-5 fw-semibold mb-2">{{ $activity->description }}</div>
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <div class="text-muted me-2 fs-7">Pada {{ $activity->created_at->diffForHumans() }} oleh</div>
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip" title="{{ $activity->causer?->name }}">
                                            <img src="{{ $activity->causer?->image ?? asset('assets/media/avatars/blank.png') }}" alt="img" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        {{-- <div class="card-footer py-5 text-center" id="kt_activities_footer">
            <a href="" class="btn btn-bg-body text-primary">View All Activities <i class="ki-filled ki-arrow-right fs-3 text-primary"></i></a>
        </div> --}}
    </div>
</div>