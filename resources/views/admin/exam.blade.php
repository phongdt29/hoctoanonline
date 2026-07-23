@extends('layouts.admin')

@section('title', 'Đề: ' . $e->title)
@section('page-title', $e->title)

@push('head')
    @if ($e->isGenerating())
        <meta http-equiv="refresh" content="5">
    @endif
@endpush

@section('page-actions')
    <a href="{{ route('admin.exams') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Kho đề</a>
    @if ($e->status === 'failed' || $e->isReady())
        <form method="POST" action="{{ route('admin.exams.retry', $e) }}" class="d-inline">@csrf
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Tạo lại</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.exams.destroy', $e) }}" class="d-inline"
          onsubmit="return confirm('Xoá đề này?')">@csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xoá</button>
    </form>
@endsection

@section('content')
@if (session('status'))<div class="alert alert-success py-2 small">{{ session('status') }}</div>@endif
@if (session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger py-2 small">@foreach ($errors->all() as $m)<div>{{ $m }}</div>@endforeach</div>@endif

<div class="text-secondary small mb-3">Lớp {{ $e->grade }} · {{ $e->question_count }} câu · độ khó {{ $e->difficulty }}@if ($e->topics) · {{ $e->topics }}@endif</div>

@if ($e->isGenerating())
    <x-card><div class="text-center py-5">
        <div class="spinner-border text-primary mb-3"></div>
        <h2 class="h5">AI đang soạn đề trắc nghiệm…</h2>
        <p class="text-secondary small mb-0">Trang tự cập nhật mỗi 5 giây.</p>
    </div></x-card>
@elseif ($e->status === 'failed')
    <x-card><div class="text-center py-4">
        <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
        <h2 class="h5 mt-2">Tạo đề thất bại</h2>
        <p class="text-secondary small">{{ $e->error ?: 'Lỗi không xác định.' }}</p>
        <form method="POST" action="{{ route('admin.exams.retry', $e) }}">@csrf
            <button class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Thử lại</button>
        </form>
    </div></x-card>
@else
    @php $questions = $e->questions(); $letters = ['A','B','C','D']; @endphp

    <div class="row g-4">
        {{-- In theo ma de --}}
        <div class="col-lg-6">
            <x-card title="In đề & đáp án theo mã đề" icon="bi-printer">
                <p class="text-secondary small">Mỗi mã đề trộn thứ tự câu và lựa chọn khác nhau (in ra khác nhau, chấm theo đúng mã).</p>
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($codes as $c)
                            <tr>
                                <td class="fw-semibold">{{ $c === 'goc' ? 'Gốc' : 'Mã ' . $c }}</td>
                                <td class="text-end">
                                    <a target="_blank" class="btn btn-sm btn-outline-primary"
                                       href="{{ route('admin.exams.print', $e) }}?code={{ $c }}&sheet=de"><i class="bi bi-file-earmark-text"></i> In đề</a>
                                    <a target="_blank" class="btn btn-sm btn-outline-secondary"
                                       href="{{ route('admin.exams.print', $e) }}?code={{ $c }}&sheet=key"><i class="bi bi-key"></i> In đáp án</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-card>
        </div>

        {{-- Cham nhanh --}}
        <div class="col-lg-6">
            <x-card title="Chấm nhanh" icon="bi-check2-square">
                <form method="POST" action="{{ route('admin.exams.grade', $e) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Mã đề</label>
                        <select name="code" class="form-select form-select-sm">
                            @foreach ($codes as $c)
                                <option value="{{ $c }}" @selected(($result['code'] ?? 'goc') === $c)>{{ $c === 'goc' ? 'Gốc' : $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-semibold">Bài làm ({{ count($questions) }} câu)</label>
                        <input name="answers" class="form-control form-control-sm font-num" required
                               placeholder="vd: ABCDABCD..." value="{{ old('answers') }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Chấm</button>
                    </div>
                </form>

                @if ($result)
                    <div class="mt-3 p-3 rounded-3 border" style="border-color:var(--ht-line) !important">
                        <div class="h4 mb-1"><span class="ht-text-grad">{{ number_format($result['score'], 2) }}</span> / 10
                            <span class="small text-secondary">— đúng {{ $result['correct_count'] }}/{{ $result['total'] }} (mã {{ $result['code'] === 'goc' ? 'Gốc' : $result['code'] }})</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2 small font-num">
                            @foreach ($result['detail'] as $i => $d)
                                <span class="badge rounded-pill {{ $d['ok'] ? 'text-bg-success' : 'text-bg-danger' }}"
                                      title="Câu {{ $i + 1 }}: chọn {{ $d['given'] }}, đáp án {{ $d['key'] }}">
                                    {{ $i + 1 }}{{ $d['given'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Xem cau hoi (danh cho giao vien, co danh dau dap an) --}}
    <x-card title="Nội dung đề (bản gốc — có đánh dấu đáp án)" icon="bi-list-ol" class="mt-4">
        @foreach ($questions as $qi => $q)
            <div class="border rounded-3 p-3 mb-2" style="border-color:var(--ht-line) !important">
                <div class="fw-semibold mb-2">
                    <span class="ht-text-grad">Câu {{ $qi + 1 }}.</span>
                    <span class="lesson-theory d-inline">{!! nl2br(e($q['content'])) !!}</span>
                    <span class="badge rounded-pill text-bg-light border ms-1">{{ $q['difficulty'] }}</span>
                </div>
                <div class="row g-2">
                    @foreach ($q['options'] as $oi => $opt)
                        <div class="col-md-6">
                            <div class="d-flex gap-2 p-2 rounded-3 {{ $oi === $q['correct'] ? 'text-bg-success' : '' }}"
                                 style="{{ $oi === $q['correct'] ? '' : 'border:1px solid var(--ht-line)' }}">
                                <strong>{{ $letters[$oi] }}.</strong>
                                <span class="lesson-theory">{!! nl2br(e($opt)) !!}</span>
                                @if ($oi === $q['correct'])<i class="bi bi-check-lg ms-auto"></i>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </x-card>
@endif
@endsection
