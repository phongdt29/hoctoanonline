@extends('layouts.admin')

@section('title', 'Báo cáo hệ thống')
@section('page-title', 'Báo cáo tổng quan hệ thống')

@section('topbar-chips')
    <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="btn btn-sm btn-outline-primary">Đăng xuất</button>
    </form>
@endsection

@section('page-actions')
    <a href="{{ url('/admin/ai-providers') }}" class="btn btn-sm btn-outline-primary d-none">AI Providers</a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> In</button>
@endsection

@section('content')
@php
    $vnd = fn ($n) => number_format((int) $n, 0, ',', '.') . 'đ';
    $num = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

{{-- KPI chinh --}}
<div class="row g-3 mb-4">
    @foreach ([
        ['bi-people-fill', 'Tổng người dùng', $num($r['users']['total']), 'grad'],
        ['bi-mortarboard-fill', 'Học sinh', $num($r['users']['by_role']['student'] ?? 0), ''],
        ['bi-cash-coin', 'Doanh thu', $vnd($r['revenue']['revenue_total']), ''],
        ['bi-robot', 'Lượt gọi A.I (7 ngày)', $num($r['ai']['calls_7d']), ''],
    ] as [$icon, $label, $value, $variant])
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="d-flex align-items-center gap-3">
                    <span class="ht-ico {{ $variant === 'grad' ? 'ht-ico-grad' : '' }}" style="width:48px;height:48px;font-size:1.35rem">
                        <i class="bi {{ $icon }}"></i>
                    </span>
                    <div>
                        <div class="text-secondary small">{{ $label }}</div>
                        <div class="num fs-4 fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    @endforeach
</div>

<div class="row g-3">
    {{-- Pheu chuyen doi --}}
    <div class="col-12 col-lg-7">
        <x-card title="Phễu chuyển đổi học sinh" icon="bi-funnel">
            @php
                $stages = $r['funnel']['stages'];
                $labels = [
                    'registered' => 'Đăng ký', 'onboarded' => 'Hoàn thiện hồ sơ',
                    'assessed' => 'Làm bài test', 'classified' => 'Đã phân loại',
                    'curriculum_active' => 'Có lộ trình', 'learning' => 'Đang học',
                ];
                $maxStage = max(1, max($stages));
            @endphp
            @foreach ($labels as $key => $label)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $label }}</span>
                        <span class="num fw-semibold">{{ $num($stages[$key] ?? 0) }}</span>
                    </div>
                    <div class="progress" style="height:.55rem">
                        <div class="progress-bar" style="width:{{ round(($stages[$key] ?? 0) / $maxStage * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
            <div class="d-flex justify-content-between small text-secondary mt-3 pt-2 border-top">
                <span>Đã chấm bài test: <span class="num fw-semibold">{{ $num($r['funnel']['assessment_count']) }}</span></span>
                <span>Tỉ lệ đến "đang học": <span class="num fw-semibold text-primary">{{ $r['funnel']['conversion_to_learning'] }}%</span></span>
            </div>
        </x-card>
    </div>

    {{-- Phan bo rui ro --}}
    <div class="col-12 col-lg-5">
        <x-card title="Phân bố rủi ro học sinh" icon="bi-shield-exclamation">
            <div class="row g-3 text-center">
                @foreach ([
                    ['🟢', 'Ổn định', $r['risk']['on_dinh'], 'var(--ht-ok)'],
                    ['🟡', 'Cần theo dõi', $r['risk']['can_theo_doi'], 'var(--ht-warn)'],
                    ['🔴', 'Nguy cơ cao', $r['risk']['nguy_co_cao'], 'var(--ht-danger)'],
                ] as [$emoji, $label, $count, $color])
                    <div class="col-4">
                        <div class="num fs-3 fw-bold" style="color:{{ $color }}">{{ $num($count) }}</div>
                        <div class="small text-secondary">{{ $emoji }} {{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Doanh thu" icon="bi-graph-up-arrow" class="mt-3">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary small">Giao dịch thành công</span>
                <span class="num fw-semibold">{{ $num($r['revenue']['paid_count']) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary small">Tổng doanh thu</span>
                <span class="num fw-semibold">{{ $vnd($r['revenue']['revenue_total']) }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-secondary small">30 ngày gần đây</span>
                <span class="num fw-semibold text-primary">{{ $vnd($r['revenue']['revenue_30d']) }}</span>
            </div>
        </x-card>
    </div>

    {{-- A.I usage --}}
    <div class="col-12 col-lg-5">
        <x-card title="Hoạt động A.I (7 ngày)" icon="bi-cpu">
            <div class="row g-2 mb-3">
                <div class="col-4"><div class="text-secondary small">Lượt gọi</div><div class="num fs-5 fw-bold">{{ $num($r['ai']['calls_7d']) }}</div></div>
                <div class="col-4"><div class="text-secondary small">Lỗi</div><div class="num fs-5 fw-bold text-danger">{{ $num($r['ai']['errors_7d']) }}</div></div>
                <div class="col-4"><div class="text-secondary small">Độ trễ TB</div><div class="num fs-5 fw-bold">{{ $num($r['ai']['avg_latency_ms']) }}ms</div></div>
            </div>
            <div class="small text-secondary mb-1">Theo tính năng:</div>
            @forelse ($r['ai']['by_feature'] as $feature => $count)
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $feature }}</span><span class="num">{{ $num($count) }}</span>
                </div>
            @empty
                <div class="text-secondary small">Chưa có dữ liệu.</div>
            @endforelse
        </x-card>
    </div>

    {{-- System counts --}}
    <div class="col-12 col-lg-7">
        <x-card title="Toàn bộ dữ liệu hệ thống" icon="bi-database">
            <div class="row g-3">
                @foreach ([
                    ['Bài kiểm tra', $r['system']['assessments'], 'bi-clipboard-check'],
                    ['Giáo trình', $r['system']['curricula'], 'bi-map'],
                    ['Bài học', $r['system']['lessons'], 'bi-book'],
                    ['Lượt làm quiz', $r['system']['quiz_attempts'], 'bi-ui-checks'],
                    ['Giải bài (solver)', $r['system']['solver_requests'], 'bi-calculator'],
                    ['Tin nhắn gia sư', $r['system']['tutor_messages'], 'bi-chat-dots'],
                    ['Phiên học', $r['system']['attendance_sessions'], 'bi-clock-history'],
                    ['Log hoạt động', $r['system']['activity_logs'], 'bi-activity'],
                    ['Huy hiệu đã trao', $r['system']['badges_earned'], 'bi-award'],
                    ['Lớp học', $r['system']['classes'], 'bi-easel'],
                    ['Bài tập giao', $r['system']['assignments'], 'bi-journal-text'],
                    ['Gói cước', $r['system']['plans'], 'bi-box-seam'],
                ] as [$label, $count, $icon])
                    <div class="col-6 col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="ht-ico" style="width:36px;height:36px;font-size:1rem"><i class="bi {{ $icon }}"></i></span>
                            <div>
                                <div class="num fw-bold">{{ $num($count) }}</div>
                                <div class="text-secondary" style="font-size:12px">{{ $label }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 gap-sm-4 small text-secondary mt-3 pt-2 border-top">
                <span>Bài học hoàn thành: <b class="num">{{ $num($r['learning']['lessons_completed']) }}</b></span>
                <span>Điểm quiz TB toàn hệ thống: <b class="num">{{ $r['learning']['avg_quiz_score'] ?? '—' }}</b></span>
            </div>
        </x-card>
    </div>

    {{-- Top hoc sinh --}}
    <div class="col-12 col-lg-6">
        <x-card title="Top học sinh tích cực" icon="bi-trophy">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="small text-secondary"><th>#</th><th>Học sinh</th><th class="text-end">Điểm</th><th class="text-end">Streak</th></tr></thead>
                    <tbody>
                        @foreach ($r['top_students'] as $s)
                            <tr>
                                <td class="num">{{ $s['rank'] }}</td>
                                <td class="fw-semibold">{{ $s['name'] }}</td>
                                <td class="text-end num">{{ $num($s['points']) }}</td>
                                <td class="text-end num">🔥{{ $s['streak'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Giao dich gan nhat --}}
    <div class="col-12 col-lg-6">
        <x-card title="Giao dịch gần đây" icon="bi-receipt">
            @if (empty($r['recent_payments']))
                <p class="text-secondary small mb-0">Chưa có giao dịch nào.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr class="small text-secondary"><th>Mã đơn</th><th>Gói</th><th class="text-end">Tiền</th><th>Cổng</th><th>Trạng thái</th></tr></thead>
                        <tbody>
                            @foreach ($r['recent_payments'] as $p)
                                <tr>
                                    <td class="num small">{{ \Illuminate\Support\Str::limit($p['order_id'], 12, '') }}</td>
                                    <td class="small">{{ $p['plan'] }}</td>
                                    <td class="text-end num small">{{ $vnd($p['amount']) }}</td>
                                    <td class="small text-uppercase">{{ $p['gateway'] }}</td>
                                    <td>
                                        @php $st = ['paid'=>['ok','Đã trả'],'pending'=>['warn','Chờ'],'failed'=>['danger','Lỗi']][$p['status']] ?? ['muted',$p['status']]; @endphp
                                        <x-status-chip :status="$st[0]">{{ $st[1] }}</x-status-chip>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection
