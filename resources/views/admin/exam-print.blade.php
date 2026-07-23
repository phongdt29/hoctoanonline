@extends('layouts.base')

@section('title', ($sheet === 'key' ? 'Đáp án' : 'Đề') . ' — ' . $e->title)

@section('body')
@php $letters = ['A','B','C','D']; $questions = $variant['questions']; $key = $variant['key']; @endphp

<div class="container py-4" style="max-width:820px">

    {{-- Thanh nut (an khi in) --}}
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="{{ route('admin.exams.show', $e) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
        <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i> In</button>
    </div>

    {{-- Tieu de --}}
    <div class="text-center mb-3">
        <div class="fw-bold" style="font-size:1.15rem">{{ $e->title }}</div>
        <div class="small text-secondary">
            Môn Toán · Lớp {{ $e->grade }} · {{ count($questions) }} câu ·
            Mã đề: <strong>{{ $code === 'goc' ? 'GỐC' : $code }}</strong>
        </div>
    </div>

    @if ($sheet === 'key')
        {{-- ===== BANG DAP AN ===== --}}
        <h2 class="h6">Đáp án — mã đề {{ $code === 'goc' ? 'Gốc' : $code }}</h2>
        <div class="d-flex flex-wrap gap-2 font-num">
            @foreach ($key as $i => $ans)
                <span class="badge rounded-pill text-bg-light border" style="min-width:64px">
                    Câu {{ $i + 1 }}: <strong>{{ $ans }}</strong>
                </span>
            @endforeach
        </div>
    @else
        {{-- ===== DE (khong dap an) ===== --}}
        <div class="d-flex justify-content-between small mb-3">
            <div>Họ tên: ......................................................</div>
            <div>Lớp: ................ Điểm: ............</div>
        </div>

        @foreach ($questions as $qi => $q)
            <div class="mb-3" style="break-inside:avoid">
                <div class="fw-semibold mb-1">
                    <span>Câu {{ $qi + 1 }}.</span>
                    <span class="lesson-theory d-inline">{!! nl2br(e($q['content'])) !!}</span>
                </div>
                <div class="row g-1 ms-1">
                    @foreach ($q['options'] as $oi => $opt)
                        <div class="col-md-6 d-flex gap-2">
                            <strong>{{ $letters[$oi] }}.</strong>
                            <span class="lesson-theory">{!! nl2br(e($opt)) !!}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Doi MathJax typeset xong roi moi mo hop in (neu khong cong thuc se in ra $...$).
    (function printWhenReady() {
        if (window.MathJax && window.MathJax.startup && window.MathJax.startup.promise) {
            window.MathJax.startup.promise.then(function () { setTimeout(function () { window.print(); }, 200); });
        } else {
            setTimeout(printWhenReady, 200);
        }
    })();
</script>
@endpush
