@extends('layouts.admin')

@section('title', $class->name)
@section('page-title', $class->name . ' · Khối ' . $class->grade)

@section('page-actions')
    <a href="{{ route('teacher.classes') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Danh sách lớp</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<div class="row g-3">
    {{-- Bai tap --}}
    <div class="col-lg-7">
        <x-card title="Bài tập đã giao" icon="bi-journal-text" class="mb-3">
            @forelse ($class->assignments as $a)
                <a href="{{ route('teacher.assignment', $a) }}"
                   class="d-flex align-items-center gap-3 rounded-3 p-3 mb-2 text-decoration-none"
                   style="border:1px solid var(--ht-line)">
                    <span class="ht-ico" style="width:38px;height:38px"><i class="bi bi-file-text"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small text-body">{{ $a->title }}</div>
                        <div class="text-secondary" style="font-size:12px">
                            Hạn: {{ $a->due_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                            @if ($a->isOverdue()) · <span class="text-danger">đã hết hạn</span> @endif
                            · <span class="num">{{ $a->submissions_count }}</span>/{{ $class->students->count() }} đã nộp
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-secondary"></i>
                </a>
            @empty
                <p class="text-secondary small mb-0">Chưa giao bài nào. Dùng form bên phải để giao bài đầu tiên.</p>
            @endforelse
        </x-card>

        {{-- Danh sach hoc sinh --}}
        <x-card title="Học sinh trong lớp" icon="bi-people">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="small text-secondary"><th>#</th><th>Học sinh</th><th>Khối</th><th class="text-end">Điểm</th></tr></thead>
                    <tbody>
                        @forelse ($class->students as $i => $s)
                            <tr>
                                <td class="num">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $s->full_name }}</td>
                                <td class="num">{{ $s->grade }}</td>
                                <td class="text-end num">{{ number_format($s->points_balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary small">Lớp chưa có học sinh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Giao bai moi --}}
    <div class="col-lg-5">
        <x-card title="Giao bài tập mới" icon="bi-plus-circle">
            <form method="POST" action="{{ route('teacher.assignment.store', $class) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tiêu đề</label>
                    <input name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}" placeholder="VD: Bài tập phân số tuần 1">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nội dung</label>
                    <textarea name="content" rows="4" class="form-control @error('content') is-invalid @enderror"
                              placeholder="Mô tả bài tập, yêu cầu...">{{ old('content') }}</textarea>
                    @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Hạn nộp</label>
                    <input type="datetime-local" name="due_at" class="form-control @error('due_at') is-invalid @enderror"
                           value="{{ old('due_at') }}">
                    @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary w-100">Giao bài</button>
            </form>
        </x-card>
    </div>
</div>
@endsection
