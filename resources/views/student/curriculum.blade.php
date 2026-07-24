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
        $allLessons  = $curriculum->modules->flatMap->lessons;
        $byPhase     = $curriculum->modules->groupBy('phase');
        $total       = $allLessons->count();
        $done        = $allLessons->where('status', 'completed')->count();
        $percent     = $total ? (int) round($done / $total * 100) : 0;
        // Buoi tiep theo: dang hoc do -> hoac buoi vua mo.
        $next        = $allLessons->firstWhere('status', 'in_progress') ?? $allLessons->firstWhere('status', 'unlocked');

        $statusMeta = fn ($s) => match ($s) {
            'completed'   => ['bi-check-circle-fill', 'text-success', 'Đã xong'],
            'in_progress' => ['bi-play-circle-fill', 'text-primary', 'Đang học'],
            'unlocked'    => ['bi-unlock', 'text-primary', 'Mở'],
            default       => ['bi-lock', 'text-secondary', 'Chưa mở'],
        };
    @endphp

    {{-- Buoi tiep theo --}}
    @if ($next)
        <div class="card border-0 mb-4" style="background:var(--ht-gradient); color:#fff; box-shadow:var(--ht-shadow-lg)">
            <div class="card-body p-4 d-flex align-items-center gap-3 flex-wrap">
                <div class="ht-ico" style="background:rgba(255,255,255,.2); color:#fff; width:52px; height:52px; font-size:1.4rem">
                    <i class="bi bi-play-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="small opacity-75">{{ $next->status === 'in_progress' ? 'Tiếp tục học' : 'Buổi tiếp theo' }}</div>
                    <div class="h5 mb-0 fw-bold">{{ $next->title }}</div>
                </div>
                <a href="{{ route('lessons.show', $next->id) }}" class="btn btn-light fw-semibold px-4" style="color:var(--ht-primary)">
                    Vào học <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- Tong quan --}}
    <x-card class="mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h2 class="h6 mb-1">{{ $curriculum->goal }}</h2>
                <p class="text-secondary small mb-0">
                    Kế hoạch {{ $curriculum->planned_sessions }} buổi ·
                    Đã xong <span class="num fw-semibold">{{ $done }}</span>/<span class="num">{{ $total }}</span> buổi
                </p>
            </div>
            <div style="min-width:160px">
                <x-mastery-grid :lessons="$allLessons" label="Toàn lộ trình" />
            </div>
        </div>
        <div class="progress" role="progressbar" aria-label="Tiến độ toàn lộ trình"
             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height:10px">
            <div class="progress-bar" style="width:{{ $percent }}%"></div>
        </div>
        <div class="text-end small text-secondary mt-1 num">{{ $percent }}%</div>
    </x-card>

    {{-- 4 giai doan — accordion, chi mo giai doan dang hoc --}}
    <div class="accordion" id="phaseAcc">
        @foreach ($phaseNames as $phase => [$name, $icon])
            @if ($byPhase->has($phase))
                @php
                    $modules   = $byPhase->get($phase);
                    $pLessons  = $modules->flatMap->lessons;
                    $pTotal    = $pLessons->count();
                    $pDone     = $pLessons->where('status', 'completed')->count();
                    $pPercent  = $pTotal ? (int) round($pDone / $pTotal * 100) : 0;
                    $isActive  = $next && $pLessons->contains('id', $next->id);
                    $allDone   = $pDone === $pTotal && $pTotal > 0;
                @endphp
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $isActive ? '' : 'collapsed' }} gap-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#phase{{ $phase }}">
                            <span class="ht-ico" style="width:38px;height:38px">
                                <i class="bi {{ $allDone ? 'bi-check-lg' : $icon }}"></i>
                            </span>
                            <span class="flex-grow-1">
                                <span class="fw-semibold">Giai đoạn {{ $phase }}: {{ $name }}</span>
                                <span class="d-block small text-secondary num">{{ $pDone }}/{{ $pTotal }} buổi
                                    @if ($allDone) · hoàn thành @endif
                                </span>
                            </span>
                            <span class="me-2" style="width:70px">
                                <span class="progress" style="height:6px">
                                    <span class="progress-bar" style="width:{{ $pPercent }}%"></span>
                                </span>
                            </span>
                        </button>
                    </h3>
                    <div id="phase{{ $phase }}" class="accordion-collapse collapse {{ $isActive ? 'show' : '' }}"
                         data-bs-parent="#phaseAcc">
                        <div class="accordion-body">
                            @foreach ($modules as $module)
                                <div class="text-secondary small fw-semibold text-uppercase mb-2" style="letter-spacing:.02em">
                                    {{ str_replace('_', ' ', $module->topic) }}
                                </div>
                                <div class="mb-3">
                                    @foreach ($module->lessons as $lesson)
                                        @php [$li, $lc, $ll] = $statusMeta($lesson->status); @endphp
                                        <div class="d-flex align-items-center gap-3 py-2 border-bottom"
                                             style="border-color:var(--ht-line) !important; {{ $next && $lesson->id === $next->id ? 'background:rgba(var(--ht-primary-rgb),.06); border-radius:10px; padding-left:.5rem; padding-right:.5rem;' : '' }}">
                                            <i class="bi {{ $li }} {{ $lc }} fs-5" aria-hidden="true"></i>
                                            <div class="flex-grow-1">
                                                <div class="small fw-semibold">{{ $lesson->title }}</div>
                                                <div class="text-secondary" style="font-size:12px">{{ $ll }}</div>
                                            </div>
                                            @if ($lesson->isAccessible())
                                                <a href="{{ route('lessons.show', $lesson->id) }}"
                                                   class="btn btn-sm {{ $next && $lesson->id === $next->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                                    Vào học
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
@endsection
