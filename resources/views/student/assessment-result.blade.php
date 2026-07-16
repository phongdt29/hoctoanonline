@extends('layouts.base')

@section('title', 'Kết quả kiểm tra')

@section('body')
<main class="container py-4 py-lg-5">
    <div class="mx-auto" style="max-width:720px">

        <div class="text-center mb-4">
            <h1 class="h4 mb-1">Kết quả bài kiểm tra</h1>
            <p class="text-secondary small mb-0">A.I đã phân tích và xây lộ trình riêng cho bạn.</p>
        </div>

        {{-- Diem + nhan --}}
        <div class="row g-3 mb-4">
            <div class="col-6">
                <x-card>
                    <x-stat label="Điểm bài test" :value="number_format((float) $assessment->score, 1)" unit="/10" icon="bi-clipboard-check" />
                </x-card>
            </div>
            <div class="col-6">
                <x-card>
                    @php
                        $levelLabel = ['trung_binh' => 'Trung bình', 'kha' => 'Khá', 'gioi' => 'Giỏi'];
                    @endphp
                    <x-stat label="Xếp loại năng lực"
                            :value="$levelLabel[$classification->final_level] ?? $classification->final_level"
                            icon="bi-bar-chart-line" />
                    @if ($classification->aiOverrodeBaseLevel())
                        <p class="text-secondary mb-0" style="font-size:12px">
                            A.I điều chỉnh dựa trên bài làm thật, không chỉ theo điểm trung bình.
                        </p>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- Nang luc theo chuyen de — thanh mau --}}
        <x-card title="Năng lực theo chủ đề" icon="bi-diagram-3" class="mb-4">
            @forelse ($classification->topicAbilities as $ta)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ str_replace('_', ' ', $ta->topic) }}</span>
                        <span class="num text-secondary">{{ $ta->ability }}/100</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="{{ $ta->topic }}"
                         aria-valuenow="{{ $ta->ability }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width:{{ $ta->ability }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-secondary small mb-0">Chưa có dữ liệu chi tiết.</p>
            @endforelse
        </x-card>

        {{-- Nhom kien thuc yeu --}}
        @if (! empty($classification->weak_topics))
            <x-card title="Cần ưu tiên củng cố" icon="bi-exclamation-triangle" class="mb-4">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($classification->weak_topics as $topic)
                        <x-status-chip status="warn">{{ str_replace('_', ' ', $topic) }}</x-status-chip>
                    @endforeach
                </div>
                <p class="text-secondary small mt-3 mb-0">
                    Lộ trình của bạn bắt đầu bằng các chủ đề này để lấy lại nền tảng.
                </p>
            </x-card>
        @endif

        <div class="text-center">
            <a href="{{ route('curriculum') }}" class="btn btn-primary btn-lg ht-tap">
                Xem lộ trình của tôi <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</main>
@endsection
