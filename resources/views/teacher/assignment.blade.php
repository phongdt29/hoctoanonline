@extends('layouts.admin')

@section('title', 'Chấm bài · ' . $assignment->title)
@section('page-title', 'Chấm bài: ' . $assignment->title)

@section('page-actions')
    <a href="{{ route('teacher.class', $class) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Về lớp</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<x-card class="mb-3">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <div>
            <div class="fw-semibold">{{ $assignment->title }}</div>
            <div class="text-secondary small">{{ $assignment->content }}</div>
        </div>
        <span class="badge rounded-pill text-bg-light border align-self-start">
            Hạn: {{ $assignment->due_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
        </span>
    </div>
</x-card>

<x-card title="Bài nộp của học sinh" icon="bi-inbox">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr class="small text-secondary">
                <th>Học sinh</th><th>Bài làm</th><th>Nộp lúc</th><th style="width:230px">Chấm điểm</th>
            </tr></thead>
            <tbody>
                @foreach ($class->students as $student)
                    @php $sub = $submissions->get($student->id); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $student->full_name }}</td>
                        <td class="small" style="max-width:260px">
                            @if ($sub)
                                <div class="text-truncate">{{ $sub->content ?: '(đính kèm file)' }}</div>
                                @if ($sub->file_url)
                                    <a href="{{ $sub->file_url }}" class="small" target="_blank"><i class="bi bi-paperclip"></i> File</a>
                                @endif
                            @else
                                <span class="text-secondary">— chưa nộp —</span>
                            @endif
                        </td>
                        <td class="small text-secondary num">
                            {{ $sub?->submitted_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i') ?? '—' }}
                        </td>
                        <td>
                            @if ($sub)
                                <form method="POST" action="{{ route('teacher.grade', $sub) }}" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <input type="number" name="score" step="0.1" min="0" max="10" required
                                           value="{{ $sub->score }}" class="form-control form-control-sm num" style="width:70px"
                                           placeholder="0-10">
                                    <input name="feedback" value="{{ $sub->feedback }}" class="form-control form-control-sm"
                                           placeholder="Nhận xét">
                                    <button class="btn btn-sm btn-primary">
                                        {{ $sub->isGraded() ? 'Sửa' : 'Chấm' }}
                                    </button>
                                </form>
                                @if ($sub->isGraded())
                                    <span class="badge text-bg-success rounded-pill mt-1">Đã chấm: <span class="num">{{ $sub->score }}</span></span>
                                @endif
                            @else
                                <span class="text-secondary small">Chưa có bài để chấm</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
