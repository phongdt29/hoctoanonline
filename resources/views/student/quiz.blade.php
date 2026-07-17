@extends('layouts.student')

@section('title', 'Quiz cuối buổi')
@section('greeting', 'Quiz cuối buổi')
@php $active = 'curriculum'; @endphp

@section('content')
<div class="mx-auto" style="max-width:720px">

    {{-- Intro --}}
    <div id="qz-intro" class="card ht-rise">
        <div class="card-body p-4 p-sm-5 text-center">
            <span class="ht-ico ht-ico-grad mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem"><i class="bi bi-ui-checks"></i></span>
            <h1 class="h5 mb-2">{{ $lesson->title }}</h1>
            <p class="text-secondary">Quiz {{ $quiz->duration_minutes }} phút. Làm hết sức nhé — đạt điểm cao sẽ mở khóa buổi tiếp theo!</p>
            <button id="qz-start" class="btn btn-primary btn-lg ht-tap"
                    data-start="{{ route('api.quizzes.start', $quiz) }}"
                    data-submit="{{ route('api.quizzes.submit', $quiz) }}">
                Bắt đầu làm quiz
            </button>
        </div>
    </div>

    {{-- Lam bai --}}
    <div id="qz-play" class="d-none">
        {{-- Timer --}}
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <span class="text-secondary small">Câu <span id="qz-pos" class="num fw-semibold">1</span>/<span id="qz-total" class="num">0</span></span>
                <span class="badge rounded-pill text-bg-light border fs-6">
                    <i class="bi bi-clock"></i> <span id="qz-timer" class="num">--:--</span>
                </span>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <div id="qz-stage"><!-- render boi quiz.js --></div>
                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <button id="qz-prev" class="btn btn-outline-primary ht-tap" disabled><i class="bi bi-arrow-left"></i> Câu trước</button>
                    <button id="qz-next" class="btn btn-primary ht-tap">Câu sau <i class="bi bi-arrow-right"></i></button>
                    <button id="qz-submit" class="btn btn-primary ht-tap d-none">Nộp bài</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Ket qua --}}
    <div id="qz-result" class="d-none">
        <div class="card ht-rise text-center">
            <div class="card-body p-4 p-sm-5">
                <div id="qz-result-ico" class="mb-3"></div>
                <h2 class="h4 mb-1">Kết quả</h2>
                <div class="num fw-bold text-primary my-2" style="font-size:3rem"><span id="qz-score">0</span><span class="fs-5 text-secondary">/10</span></div>
                <p id="qz-msg" class="text-secondary mb-4"></p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="{{ route('curriculum') }}" class="btn btn-primary ht-tap">Về lộ trình</a>
                    <a href="{{ route('lessons.show', $lesson->id) }}" class="btn btn-outline-primary ht-tap">Xem lại bài học</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quiz.js') }}?v={{ filemtime(public_path('js/quiz.js')) }}"></script>
@endpush
