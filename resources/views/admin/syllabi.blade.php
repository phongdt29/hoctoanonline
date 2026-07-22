@extends('layouts.admin')

@section('title', 'Giáo trình AI')
@section('page-title', 'Lên giáo trình bằng AI')

@section('page-actions')
    <a href="{{ route('admin.home') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Về báo cáo</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="row g-4">
    {{-- Form tao --}}
    <div class="col-lg-5">
        <x-card title="Tạo giáo trình mới" icon="bi-magic">
            <form method="POST" action="{{ route('admin.syllabi.store') }}">
                @csrf
                <label class="form-label small fw-semibold">Tên giáo trình</label>
                <input name="title" value="{{ old('title') }}" class="form-control mb-3" required
                       placeholder="vd: Toán 9 — Hàm số bậc nhất">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Lớp</label>
                        <select name="grade" class="form-select">
                            @for ($g = 1; $g <= 12; $g++)
                                <option value="{{ $g }}" @selected(old('grade', 9) == $g)>Lớp {{ $g }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Số buổi <span class="text-secondary">(tuỳ chọn)</span></label>
                        <input name="planned_sessions" type="number" min="0" max="40" value="{{ old('planned_sessions') }}"
                               class="form-control num" placeholder="tự động">
                    </div>
                </div>

                <label class="form-label small fw-semibold">Chủ đề trọng tâm <span class="text-secondary">(tuỳ chọn)</span></label>
                <input name="topic" value="{{ old('topic') }}" class="form-control mb-3"
                       placeholder="vd: Phương trình bậc hai, Hình học không gian…">

                <label class="form-label small fw-semibold">Mục tiêu <span class="text-secondary">(tuỳ chọn)</span></label>
                <textarea name="goal" rows="2" class="form-control mb-3"
                          placeholder="vd: Giúp học sinh nắm vững nền tảng, đạt 8+ cuối kỳ">{{ old('goal') }}</textarea>

                <button class="btn btn-primary w-100"><i class="bi bi-stars"></i> Tạo bằng AI</button>
                <div class="form-text">AI sẽ sinh khung chương → bài → lý thuyết + bài tập. Chạy nền, có thể mất 30–90 giây.</div>
            </form>
        </x-card>
    </div>

    {{-- Danh sach --}}
    <div class="col-lg-7">
        <x-card title="Thư viện giáo trình" icon="bi-collection">
            @php
                $statusChip = [
                    'draft' => ['Nháp', 'text-bg-light'],
                    'generating' => ['Đang tạo…', 'text-bg-warning'],
                    'ready' => ['Sẵn sàng', 'text-bg-success'],
                    'failed' => ['Lỗi', 'text-bg-danger'],
                ];
            @endphp
            @forelse ($syllabi as $s)
                <a href="{{ route('admin.syllabi.show', $s) }}"
                   class="d-flex align-items-center gap-3 rounded-3 p-3 mb-2 text-decoration-none border"
                   style="border-color:var(--ht-line) !important">
                    <span class="ht-ico" style="width:38px;height:38px"><i class="bi bi-journal-richtext"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-body">{{ $s->title }}</div>
                        <div class="small text-secondary">Lớp {{ $s->grade }}@if ($s->topic) · {{ $s->topic }}@endif
                            @if ($s->isReady()) · <span class="num">{{ $s->lessonCount() }}</span> bài @endif
                        </div>
                    </div>
                    @php [$label, $cls] = $statusChip[$s->status] ?? ['?', 'text-bg-light']; @endphp
                    <span class="badge rounded-pill {{ $cls }}">{{ $label }}</span>
                </a>
            @empty
                <x-empty-state icon="bi-journal-plus" title="Chưa có giáo trình nào">
                    Tạo giáo trình đầu tiên bằng form bên trái.
                </x-empty-state>
            @endforelse

            <div class="mt-3">{{ $syllabi->links('pagination::bootstrap-5') }}</div>
        </x-card>
    </div>
</div>
@endsection
