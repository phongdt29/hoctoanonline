@extends('layouts.parent')

@section('title', 'Theo dõi con')
@section('greeting', 'Theo dõi con')

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

@if ($children->isEmpty())
    {{-- Chua link con nao --}}
    <x-card title="Liên kết với con" icon="bi-link-45deg">
        <p class="text-secondary small">Nhập mã mời của con (xem trong trang cá nhân của con) để bắt đầu theo dõi.</p>
        <form method="POST" action="{{ route('parent.link-student') }}" class="row g-2">
            @csrf
            <div class="col">
                <input name="invite_code" class="form-control @error('invite_code') is-invalid @enderror"
                       placeholder="VD: HT000001" value="{{ old('invite_code') }}">
                @error('invite_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">Liên kết</button>
            </div>
        </form>
    </x-card>
@else
    {{-- Chon con --}}
    @if ($children->count() > 1)
        <div class="mb-3 d-flex gap-2 flex-wrap">
            @foreach ($children as $child)
                <a href="{{ route('parent.dashboard', ['child' => $child->id]) }}"
                   class="btn btn-sm {{ $selected?->id === $child->id ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $child->full_name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        {{-- Khoi 0: den tin hieu risk (dau, col-12) --}}
        <div class="col-12">
            <x-card>
                @php
                    $lvl = $data['risk']['level'];
                    $chip = match ($lvl) {
                        'on_dinh' => ['ok', 'Ổn định'],
                        'can_theo_doi' => ['warn', 'Cần theo dõi'],
                        'nguy_co_cao' => ['danger', 'Nguy cơ cao'],
                        default => ['muted', 'Chưa có dữ liệu'],
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="text-secondary small">Tình trạng học tập của {{ $selected->full_name }}</div>
                        <x-status-chip :status="$chip[0]" class="fs-6 mt-1">
                            {{ $chip[1] }}
                            @if ($data['risk']['score'] !== null)
                                · <span class="num">{{ $data['risk']['score'] }}</span>
                            @endif
                        </x-status-chip>
                    </div>
                    <i class="bi bi-shield-check fs-1 text-secondary opacity-25"></i>
                </div>
            </x-card>
        </div>

        {{-- 1. Lich hoc hom nay --}}
        <div class="col-12 col-md-6">
            <x-card title="Lịch học hôm nay" icon="bi-calendar-day">
                @if ($data['today_schedule']['has_session'])
                    <p class="mb-1">{{ $data['today_schedule']['lesson'] }}</p>
                    <p class="text-secondary small mb-0">Dự kiến: {{ $data['today_schedule']['scheduled_at'] }}</p>
                @else
                    <p class="text-secondary small mb-0">Hôm nay không có buổi học theo lịch.</p>
                @endif
            </x-card>
        </div>

        {{-- 2. Trang thai tham gia --}}
        <div class="col-12 col-md-6">
            <x-card title="Trạng thái tham gia" icon="bi-person-check">
                @php
                    $st = $data['participation']['status'];
                    $stChip = match ($st) {
                        'present' => ['ok', 'Có mặt'],
                        'partial' => ['warn', 'Học chưa đủ'],
                        'absent' => ['danger', 'Vắng'],
                        'late' => ['warn', 'Vào trễ'],
                        default => ['muted', 'Chưa tới giờ'],
                    };
                @endphp
                <x-status-chip :status="$stChip[0]">{{ $stChip[1] }}</x-status-chip>
            </x-card>
        </div>

        {{-- 3. Thoi gian hoc that --}}
        <div class="col-12 col-md-6">
            <x-card title="Thời gian học thật" icon="bi-clock-history">
                <p class="small mb-0">{{ $data['study_time']['summary'] ?? 'Chưa có dữ liệu buổi học.' }}</p>
            </x-card>
        </div>

        {{-- 4. Ket qua buoi hoc --}}
        <div class="col-12 col-md-6">
            <x-card title="Kết quả buổi học" icon="bi-clipboard-check">
                @if ($data['session_result']['completion_rate'] !== null)
                    <x-stat label="Hoàn thành buổi"
                            :value="number_format((float) $data['session_result']['completion_rate'], 0)" unit="%" />
                @else
                    <p class="text-secondary small mb-0">Chưa có kết quả.</p>
                @endif
            </x-card>
        </div>

        {{-- 5. Canh bao bat thuong --}}
        <div class="col-12 col-md-6">
            <x-card title="Cảnh báo" icon="bi-exclamation-triangle">
                @forelse ($data['alerts'] as $alert)
                    <div class="mb-2"><x-status-chip :status="$alert['level']">{{ $alert['text'] }}</x-status-chip></div>
                @empty
                    <p class="text-secondary small mb-0">Không có cảnh báo nào. 🎉</p>
                @endforelse
            </x-card>
        </div>

        {{-- 6. Goi y can thiep --}}
        <div class="col-12 col-md-6">
            <x-card title="Gợi ý can thiệp" icon="bi-lightbulb">
                <ul class="small mb-0 ps-3">
                    @foreach ($data['interventions'] as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
@endif
@endsection
