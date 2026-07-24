@extends('layouts.student')

@section('title', 'Kết quả học tập')
@section('greeting', 'Kết quả học tập')
@php $active = 'results'; @endphp

@section('content')
@if (! $curriculum)
    <x-empty-state icon="bi-graph-up-arrow" title="Chưa có kết quả"
                   message="Bắt đầu học và làm quiz để xem tiến độ của bạn ở đây." />
@else
    {{-- Tong quan: biet hoc toi dau --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <x-card><x-stat label="Hoàn thành" :value="$percent" unit="%" icon="bi-check2-circle" /></x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card><x-stat label="Buổi đã học" :value="$done" :unit="'/ ' . $total" icon="bi-journal-check" /></x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card><x-stat label="Điểm quiz TB"
                            :value="$avgScore !== null ? number_format($avgScore, 1) : '—'"
                            icon="bi-clipboard-data" /></x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card><x-stat label="Điểm cao nhất"
                            :value="$bestScore !== null ? number_format($bestScore, 1) : '—'"
                            icon="bi-trophy" /></x-card>
        </div>
    </div>

    {{-- Tien do tong + dang o dau --}}
    <x-card title="Tiến độ lộ trình" icon="bi-signpost-2" class="mb-4">
        <div class="progress mb-2" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height:14px">
            <div class="progress-bar" style="width:{{ $percent }}%">{{ $percent > 8 ? $percent . '%' : '' }}</div>
        </div>
        @if ($next)
            <div class="d-flex align-items-center gap-2 small">
                <i class="bi bi-geo-alt-fill text-primary"></i>
                <span>Đang ở: <strong>{{ $next->title }}</strong></span>
                <a href="{{ route('lessons.show', $next->id) }}" class="btn btn-sm btn-outline-primary ms-auto">Học tiếp</a>
            </div>
        @else
            <div class="small text-success"><i class="bi bi-check-circle-fill"></i> Bạn đã hoàn thành toàn bộ lộ trình!</div>
        @endif
    </x-card>

    {{-- Diem quiz qua tung buoi (bieu do cot) --}}
    <x-card title="Điểm quiz qua các buổi" icon="bi-bar-chart-line" class="mb-4">
        @if ($attempts->isEmpty())
            <p class="text-secondary small mb-0">Chưa làm quiz nào. Hoàn thành buổi học và làm quiz cuối buổi nhé.</p>
        @else
            <div class="d-flex align-items-end gap-2 overflow-auto pb-2" style="height:170px">
                @foreach ($attempts as $i => $a)
                    @php
                        $sc = (float) $a->score;
                        $cls = $sc >= 8 ? 'var(--ht-ok)' : ($sc >= 5 ? 'var(--ht-warn)' : 'var(--ht-danger)');
                        $h = max(6, (int) round($sc / 10 * 130));
                    @endphp
                    <div class="text-center flex-shrink-0" style="width:44px"
                         title="{{ $a->quiz?->lesson?->title }} — {{ number_format($sc, 1) }}/10">
                        <div class="num small fw-semibold mb-1">{{ number_format($sc, 1) }}</div>
                        <div style="height:{{ $h }}px; background:{{ $cls }}; border-radius:8px 8px 0 0"></div>
                        <div class="text-secondary num" style="font-size:11px">B{{ $i + 1 }}</div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-3 small text-secondary mt-2 flex-wrap">
                <span><span class="d-inline-block rounded" style="width:10px;height:10px;background:var(--ht-ok)"></span> Giỏi (≥8)</span>
                <span><span class="d-inline-block rounded" style="width:10px;height:10px;background:var(--ht-warn)"></span> Khá (5–8)</span>
                <span><span class="d-inline-block rounded" style="width:10px;height:10px;background:var(--ht-danger)"></span> Cần cố gắng (&lt;5)</span>
            </div>
        @endif
    </x-card>

    {{-- Nang luc theo chu de --}}
    @if ($abilities->isNotEmpty())
        <x-card title="Năng lực theo chủ đề" icon="bi-diagram-3" class="mb-4">
            @foreach ($abilities as $ta)
                @php
                    $ab = (int) $ta->ability;
                    $abCls = $ab >= 70 ? 'bg-success' : ($ab >= 40 ? 'bg-warning' : 'bg-danger');
                    $isWeak = in_array($ta->topic, $weakTopics, true);
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold text-capitalize">
                            {{ str_replace('_', ' ', $ta->topic) }}
                            @if ($isWeak) <span class="badge text-bg-danger rounded-pill ms-1">cần củng cố</span> @endif
                        </span>
                        <span class="num text-secondary">{{ $ab }}/100</span>
                    </div>
                    <div class="progress" style="height:8px" role="progressbar" aria-valuenow="{{ $ab }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $abCls }}" style="width:{{ $ab }}%"></div>
                    </div>
                </div>
            @endforeach
        </x-card>
    @endif

    {{-- Lich su quiz --}}
    @if ($attempts->isNotEmpty())
        <x-card title="Lịch sử làm quiz" icon="bi-clock-history">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="small text-secondary"><th>Buổi học</th><th class="text-end">Điểm</th><th class="text-end">Ngày</th></tr></thead>
                    <tbody>
                        @foreach ($attempts->sortByDesc('submitted_at') as $a)
                            @php $sc = (float) $a->score; @endphp
                            <tr>
                                <td class="small">{{ $a->quiz?->lesson?->title ?? '—' }}</td>
                                <td class="text-end">
                                    <span class="badge rounded-pill num {{ $sc >= 8 ? 'text-bg-success' : ($sc >= 5 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                        {{ number_format($sc, 1) }}
                                    </span>
                                </td>
                                <td class="text-end small text-secondary num">
                                    {{ $a->submitted_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endif
@endsection
