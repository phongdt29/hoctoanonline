@extends('layouts.student')

@section('title', 'Lộ trình học')
@section('greeting', 'Lộ trình của bạn')
@php $active = 'curriculum'; @endphp

@section('content')
@if (! $curriculum)
    <x-empty-state icon="bi-map"
                   title="Chưa có lộ trình"
                   message="Hoàn thành bài kiểm tra đầu vào để A.I xây lộ trình riêng cho bạn.">
        <x-slot:action>
            <a href="{{ route('assessment') }}" class="btn btn-primary">Làm bài kiểm tra</a>
        </x-slot:action>
    </x-empty-state>
@else
    @php
        $phaseNames = [
            1 => ['Ôn nền tảng', 'bi-bricks'],
            2 => ['Củng cố', 'bi-layers'],
            3 => ['Nâng cao', 'bi-graph-up-arrow'],
            4 => ['Luyện đề', 'bi-mortarboard'],
        ];
        $allLessons = $curriculum->modules->flatMap->lessons;
        $byPhase = $curriculum->modules->groupBy('phase');
    @endphp

    {{-- Tong quan --}}
    <x-card class="mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="h6 mb-1">{{ $curriculum->goal }}</h2>
                <p class="text-secondary small mb-0">Kế hoạch {{ $curriculum->planned_sessions }} buổi</p>
            </div>
            <div style="min-width:180px">
                <x-mastery-grid :lessons="$allLessons" label="Toàn lộ trình" />
            </div>
        </div>
    </x-card>

    {{-- Tung phase --}}
    @foreach ($phaseNames as $phase => [$name, $icon])
        @if ($byPhase->has($phase))
            <div class="mb-4">
                <h3 class="h6 d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill text-bg-light border num">{{ $phase }}</span>
                    <i class="bi {{ $icon }} text-primary"></i> {{ $name }}
                </h3>

                <div class="row g-3">
                    @foreach ($byPhase->get($phase) as $module)
                        @foreach ($module->lessons as $lesson)
                            <div class="col-12 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        @php
                                            $icon = match ($lesson->status) {
                                                'completed'   => ['bi-check-circle-fill text-success', 'Đã xong'],
                                                'in_progress' => ['bi-play-circle-fill text-primary', 'Đang học'],
                                                'unlocked'    => ['bi-unlock text-primary', 'Mở'],
                                                default       => ['bi-lock text-secondary', 'Chưa mở'],
                                            };
                                        @endphp
                                        <i class="bi {{ $icon[0] }} fs-4" aria-hidden="true"></i>
                                        <div class="flex-grow-1">
                                            <div class="small fw-semibold">{{ $lesson->title }}</div>
                                            <div class="text-secondary" style="font-size:12px">
                                                {{ str_replace('_', ' ', $module->topic) }} · {{ $icon[1] }}
                                            </div>
                                        </div>
                                        @if ($lesson->isAccessible())
                                            <a href="{{ route('lessons.show', $lesson->id) }}" class="btn btn-sm btn-outline-primary ht-tap">Vào học</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
