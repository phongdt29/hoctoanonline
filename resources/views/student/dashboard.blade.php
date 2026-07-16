@extends('layouts.student')

@section('title', 'Trang chính')
@section('greeting', 'Chào ' . $student->full_name . '!')
@php $active = 'dashboard'; @endphp

@section('topbar-chips')
    <span class="badge rounded-pill text-bg-light border">
        <i class="bi bi-fire text-warning"></i> <span class="num">{{ $d['streak_days'] }}</span> ngày
    </span>
    <span class="badge rounded-pill text-bg-light border">
        <i class="bi bi-star-fill" style="color:var(--ht-star)"></i> <span class="num">{{ $d['points_balance'] }}</span>
    </span>
    <a href="{{ route('pricing') }}" class="btn btn-sm btn-primary ht-tap">
        <i class="bi bi-gem"></i> Nâng cấp
    </a>
@endsection

@section('content')
<div class="row g-4">

    {{-- Thong ke --}}
    <div class="col-12">
        <x-card>
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <x-stat label="Hoàn thành" :value="$d['completion_percent']" unit="%" icon="bi-check2-circle" />
                </div>
                <div class="col-6 col-md-3">
                    <x-stat label="Đã học" :value="$d['sessions_done']" unit="buổi" icon="bi-journal-check" />
                </div>
                <div class="col-6 col-md-3">
                    <x-stat label="Còn lại" :value="$d['sessions_remaining']" unit="buổi" icon="bi-hourglass-split" />
                </div>
                <div class="col-6 col-md-3">
                    <x-stat label="Điểm TB"
                            :value="$d['avg_quiz_score'] !== null ? number_format($d['avg_quiz_score'], 1) : '—'"
                            icon="bi-clipboard-data" />
                </div>
            </div>

            <div class="progress mt-4" role="progressbar" aria-label="Tiến độ"
                 aria-valuenow="{{ $d['completion_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width:{{ $d['completion_percent'] }}%"></div>
            </div>
        </x-card>
    </div>

    {{-- Goi y hom nay: thanh mix 20/60/20 --}}
    <div class="col-12 col-lg-7">
        <x-card title="Gợi ý học hôm nay" icon="bi-lightbulb">
            @php $mix = $d['today_recommendation']['mix']; @endphp

            <div class="ht-mix mb-2" role="img"
                 aria-label="Cấu trúc buổi: ôn {{ $mix['review'] }}%, bài mới {{ $mix['new'] }}%, củng cố {{ $mix['reinforce'] }}%">
                <div class="m-review" style="width:{{ $mix['review'] }}%"></div>
                <div class="m-new" style="width:{{ $mix['new'] }}%"></div>
                <div class="m-fix" style="width:{{ $mix['reinforce'] }}%"></div>
            </div>
            <div class="d-flex justify-content-between small text-secondary mb-3">
                <span>Ôn <span class="num">{{ $mix['review'] }}%</span></span>
                <span>Bài mới <span class="num">{{ $mix['new'] }}%</span></span>
                <span>Củng cố <span class="num">{{ $mix['reinforce'] }}%</span></span>
            </div>

            @php $new = $d['today_recommendation']['new_content']['lessons'] ?? []; @endphp
            @if (! empty($new))
                <p class="small fw-semibold mb-2">Bài mới hôm nay:</p>
                @foreach ($new as $lesson)
                    <a href="{{ route('lessons.show', $lesson['id']) }}"
                       class="d-flex align-items-center gap-2 border rounded-3 p-2 mb-2 text-decoration-none">
                        <i class="bi bi-play-circle text-primary"></i>
                        <span class="small flex-grow-1 text-body">{{ $lesson['title'] }}</span>
                        <i class="bi bi-arrow-right text-secondary"></i>
                    </a>
                @endforeach
            @else
                <p class="text-secondary small mb-0">Chưa có bài mới — hãy hoàn thành buổi đang mở.</p>
            @endif
        </x-card>
    </div>

    {{-- Mastery grid + diem yeu --}}
    <div class="col-12 col-lg-5">
        <x-card title="Tiến độ lộ trình" icon="bi-grid-3x3" class="mb-4">
            <x-mastery-grid :lessons="$lessons" />
        </x-card>

        @if (! empty($d['weak_topics']))
            <x-card title="Cần củng cố" icon="bi-exclamation-triangle">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($d['weak_topics'] as $topic)
                        <x-status-chip status="warn">{{ str_replace('_', ' ', $topic) }}</x-status-chip>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</div>
@endsection
