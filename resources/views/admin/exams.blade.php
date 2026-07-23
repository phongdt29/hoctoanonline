@extends('layouts.admin')

@section('title', 'Đề thi trắc nghiệm')
@section('page-title', 'Đề thi / kiểm tra trắc nghiệm')

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
    <div class="col-lg-5">
        <x-card title="Tạo đề mới" icon="bi-ui-checks">
            <form method="POST" action="{{ route('admin.exams.store') }}">
                @csrf
                <label class="form-label small fw-semibold">Tên đề</label>
                <input name="title" value="{{ old('title') }}" class="form-control mb-3" required
                       placeholder="vd: Kiểm tra 15 phút — Phân số">

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Lớp</label>
                        <select name="grade" class="form-select">
                            @for ($g = 1; $g <= 12; $g++)
                                <option value="{{ $g }}" @selected(old('grade', 6) == $g)>Lớp {{ $g }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Số câu</label>
                        <input name="question_count" type="number" min="5" max="40" value="{{ old('question_count', 10) }}"
                               class="form-control num" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Độ khó</label>
                        <select name="difficulty" class="form-select">
                            <option value="mixed" @selected(old('difficulty', 'mixed') === 'mixed')>Hỗn hợp</option>
                            <option value="easy" @selected(old('difficulty') === 'easy')>Dễ</option>
                            <option value="medium" @selected(old('difficulty') === 'medium')>Trung bình</option>
                            <option value="hard" @selected(old('difficulty') === 'hard')>Khó</option>
                        </select>
                    </div>
                </div>

                <label class="form-label small fw-semibold">Chủ đề <span class="text-secondary">(tuỳ chọn, cách nhau dấu phẩy)</span></label>
                <input name="topics" value="{{ old('topics') }}" class="form-control mb-3"
                       placeholder="vd: Phân số, Số thập phân">

                <button class="btn btn-primary w-100"><i class="bi bi-stars"></i> Tạo đề bằng AI</button>
                <div class="form-text">AI sinh câu hỏi trắc nghiệm 4 lựa chọn. Chạy nền ~20–60 giây.</div>
            </form>
        </x-card>
    </div>

    <div class="col-lg-7">
        <x-card title="Kho đề" icon="bi-collection">
            @php $chip = ['draft'=>['Nháp','text-bg-light'],'generating'=>['Đang tạo…','text-bg-warning'],'ready'=>['Sẵn sàng','text-bg-success'],'failed'=>['Lỗi','text-bg-danger']]; @endphp
            @forelse ($exams as $e)
                <a href="{{ route('admin.exams.show', $e) }}"
                   class="d-flex align-items-center gap-3 rounded-3 p-3 mb-2 text-decoration-none border"
                   style="border-color:var(--ht-line) !important">
                    <span class="ht-ico" style="width:38px;height:38px"><i class="bi bi-ui-checks-grid"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-body">{{ $e->title }}</div>
                        <div class="small text-secondary">Lớp {{ $e->grade }} · <span class="num">{{ $e->question_count }}</span> câu
                            @if ($e->topics) · {{ $e->topics }} @endif
                        </div>
                    </div>
                    @php [$label, $cls] = $chip[$e->status] ?? ['?','text-bg-light']; @endphp
                    <span class="badge rounded-pill {{ $cls }}">{{ $label }}</span>
                </a>
            @empty
                <x-empty-state icon="bi-ui-checks" title="Chưa có đề nào">Tạo đề đầu tiên bằng form bên trái.</x-empty-state>
            @endforelse
            <div class="mt-3">{{ $exams->links('pagination::bootstrap-5') }}</div>
        </x-card>
    </div>
</div>
@endsection
