@extends('layouts.admin')

@section('title', 'Giáo trình: ' . $s->title)
@section('page-title', $s->title)

@push('head')
    {{-- Dang sinh: tu refresh 5s de cap nhat trang thai --}}
    @if ($s->isGenerating())
        <meta http-equiv="refresh" content="5">
    @endif
@endpush

@section('page-actions')
    <a href="{{ route('admin.syllabi') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Thư viện</a>
    @if ($s->status === 'failed' || $s->isReady())
        <form method="POST" action="{{ route('admin.syllabi.retry', $s) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Tạo lại</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.syllabi.destroy', $s) }}" class="d-inline"
          onsubmit="return confirm('Xoá giáo trình này?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xoá</button>
    </form>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<div class="text-secondary small mb-3">
    Lớp {{ $s->grade }}
    @if ($s->topic) · Chủ đề: <strong>{{ $s->topic }}</strong> @endif
    @if ($s->goal) · Mục tiêu: {{ $s->goal }} @endif
</div>

{{-- ===== Trang thai ===== --}}
@if ($s->isGenerating())
    <x-card>
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <h2 class="h5">AI đang soạn giáo trình…</h2>
            <p class="text-secondary small mb-0">Trang tự cập nhật mỗi 5 giây. Sinh đầy đủ lý thuyết + bài tập có thể mất 30–90 giây.</p>
        </div>
    </x-card>
@elseif ($s->status === 'failed')
    <x-card>
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
            <h2 class="h5 mt-2">Tạo giáo trình thất bại</h2>
            <p class="text-secondary small">{{ $s->error ?: 'Lỗi không xác định.' }}</p>
            <form method="POST" action="{{ route('admin.syllabi.retry', $s) }}">
                @csrf
                <button class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Thử lại</button>
            </form>
        </div>
    </x-card>
@else
    {{-- ===== Da san sang: render giao trinh ===== --}}
    @php
        $phaseLabel = [1 => 'Ôn nền tảng', 2 => 'Củng cố', 3 => 'Nâng cao', 4 => 'Luyện đề'];
        $diffLabel = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó'];
        $modules = $s->content['modules'] ?? [];
    @endphp

    <x-card class="mb-4">
        <div class="row g-3 text-center">
            <div class="col-4"><x-stat label="Chương" :value="count($modules)" icon="bi-collection" /></div>
            <div class="col-4"><x-stat label="Bài học" :value="$s->lessonCount()" icon="bi-journal-text" /></div>
            <div class="col-4"><x-stat label="Số buổi" :value="$s->planned_sessions" icon="bi-calendar-week" /></div>
        </div>
    </x-card>

    <div class="accordion" id="syllabusAcc">
        @foreach ($modules as $mi => $module)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $mi === 0 ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#mod{{ $mi }}">
                        <span class="badge rounded-pill text-bg-light border me-2">Phase {{ $module['phase'] }}</span>
                        <span class="fw-semibold">{{ $module['topic'] }}</span>
                        <span class="text-secondary small ms-2">· {{ $phaseLabel[$module['phase']] ?? '' }} · {{ count($module['lessons'] ?? []) }} bài</span>
                    </button>
                </h2>
                <div id="mod{{ $mi }}" class="accordion-collapse collapse {{ $mi === 0 ? 'show' : '' }}"
                     data-bs-parent="#syllabusAcc">
                    <div class="accordion-body">
                        @foreach ($module['lessons'] ?? [] as $li => $lesson)
                            <div class="border rounded-3 p-3 mb-3" style="border-color:var(--ht-line) !important">
                                <div class="fw-semibold mb-2">
                                    <span class="ht-text-grad">Bài {{ $li + 1 }}.</span> {{ $lesson['title'] }}
                                </div>
                                @if (! empty($lesson['theory']))
                                    <div class="lesson-theory small mb-3">{!! nl2br(e($lesson['theory'])) !!}</div>
                                @endif
                                @if (! empty($lesson['exercises']))
                                    <div class="small">
                                        @foreach ($lesson['exercises'] as $ex)
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="badge rounded-pill text-bg-light border align-self-start">{{ $diffLabel[$ex['difficulty']] ?? $ex['difficulty'] }}</span>
                                                <div class="lesson-theory">
                                                    <div>{!! nl2br(e($ex['content'])) !!}</div>
                                                    @if (! empty($ex['answer']))
                                                        <div class="text-secondary">Đáp án: {!! nl2br(e($ex['answer'])) !!}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
