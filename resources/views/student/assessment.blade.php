@extends('layouts.base')

@section('title', 'Kiểm tra đầu vào')

@section('body')
<main class="container py-4 py-lg-5">
    <div class="mx-auto" style="max-width:720px">

        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif

        {{-- Man gioi thieu --}}
        <div id="asm-intro" class="card ht-rise">
            <div class="card-body p-4 text-center">
                <i class="bi bi-clipboard-check text-primary" style="font-size:2.5rem"></i>
                <h1 class="h4 mt-3 mb-2">Bài kiểm tra đầu vào</h1>
                <p class="text-secondary">
                    A.I sẽ ra một bộ đề vừa sức để hiểu bạn đang ở đâu, rồi xây lộ trình riêng cho bạn.
                    Làm hết sức nhé — sai cũng không sao, đây không phải bài chấm điểm.
                </p>
                <ul class="list-unstyled small text-secondary mb-4">
                    <li>· Khoảng 8 câu, trắc nghiệm + tự luận</li>
                    <li>· Không giới hạn thời gian — cứ bình tĩnh</li>
                    <li>· Bài tự lưu, lỡ thoát ra vẫn làm tiếp được</li>
                </ul>
                <button id="asm-start" class="btn btn-primary btn-lg ht-tap">Bắt đầu làm bài</button>
            </div>
        </div>

        {{-- Man lam bai --}}
        <div id="asm-play" class="card d-none">
            <div class="card-body p-4">
                <div id="asm-stage"><!-- render boi assessment.js --></div>

                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <button id="asm-prev" class="btn btn-outline-primary ht-tap" disabled>
                        <i class="bi bi-arrow-left"></i> Câu trước
                    </button>
                    <button id="asm-next" class="btn btn-primary ht-tap">
                        Câu sau <i class="bi bi-arrow-right"></i>
                    </button>
                    <button id="asm-submit" class="btn btn-primary ht-tap d-none">Nộp bài</button>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    window.htAssessment = { resultUrlBase: '{{ url('/assessment') }}' };
</script>
<script src="{{ asset('js/assessment.js') }}"></script>
@endpush
