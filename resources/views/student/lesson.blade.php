@extends('layouts.student')

@section('title', $lesson->title)
@section('greeting', $lesson->title)
@php $active = 'curriculum'; @endphp

@section('content')
<div class="mx-auto" style="max-width:760px">

    {{-- Thanh dieu huong tren --}}
    <div class="print-title d-none">{{ $lesson->title }}</div>
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <a href="{{ route('curriculum') }}" class="btn btn-sm btn-outline-secondary no-print">
            <i class="bi bi-arrow-left"></i> Lộ trình
        </a>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-primary no-print">
            <i class="bi bi-printer"></i> In bài học
        </button>
    </div>

    {{-- Ly thuyet --}}
    <x-card title="Lý thuyết" icon="bi-book" class="mb-4">
        <div class="lesson-theory" style="line-height:1.75; font-size:1.02rem">{!! nl2br(e($lesson->theory_content)) !!}</div>
    </x-card>

    {{-- Bai tap --}}
    <x-card title="Bài tập tự luyện" icon="bi-pencil-square" class="mb-4">
        <p class="text-secondary small">Làm thử trước, rồi bấm <strong>Xem đáp án</strong> để đối chiếu.</p>
        @php
            $byDiff = $lesson->exercises->groupBy('difficulty');
            $diffMeta = [
                'easy'   => ['Dễ', 'text-bg-success'],
                'medium' => ['Trung bình', 'text-bg-warning'],
                'hard'   => ['Khó', 'text-bg-danger'],
            ];
            $n = 0;
        @endphp

        @foreach (['easy', 'medium', 'hard'] as $diff)
            @if ($byDiff->has($diff))
                @php [$dLabel, $dClass] = $diffMeta[$diff]; @endphp
                <h3 class="h6 mt-4 mb-2 d-flex align-items-center gap-2">
                    <span class="badge rounded-pill {{ $dClass }}">{{ $dLabel }}</span>
                </h3>
                @foreach ($byDiff->get($diff) as $ex)
                    @php $n++; $ans = $ex->answer['value'] ?? ''; @endphp
                    <div class="border rounded-3 p-3 mb-2" style="border-color:var(--ht-line) !important">
                        <div class="d-flex gap-2">
                            <span class="fw-bold" style="color:var(--ht-primary)">Câu {{ $n }}.</span>
                            <div class="lesson-theory flex-grow-1">{!! nl2br(e($ex->content)) !!}</div>
                        </div>
                        @if ($ans !== '')
                            <div class="mt-2 no-print">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#ans{{ $ex->id }}">
                                    <i class="bi bi-eye"></i> Xem đáp án
                                </button>
                            </div>
                            <div class="collapse mt-2" id="ans{{ $ex->id }}">
                                <div class="rounded-3 p-3 lesson-theory" style="background:rgba(var(--ht-primary-rgb),.06)">
                                    <span class="fw-semibold text-secondary small d-block mb-1">Đáp án</span>
                                    {!! nl2br(e($ans)) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        @endforeach

        @if ($lesson->exercises->isEmpty())
            <p class="text-secondary small mb-0">Buổi này chưa có bài tập.</p>
        @endif
    </x-card>

    {{-- Vao quiz --}}
    @if ($lesson->quiz)
        <div class="card border-0 text-center no-print" style="background:var(--ht-gradient); color:#fff; box-shadow:var(--ht-shadow-lg)">
            <div class="card-body p-4">
                <div class="h6 mb-1">Sẵn sàng kiểm tra?</div>
                <p class="mb-3 opacity-75 small">Làm quiz 15 phút để chốt kiến thức và mở buổi tiếp theo.</p>
                <a href="{{ route('quiz.show', $lesson->quiz) }}" class="btn btn-light fw-semibold px-4" style="color:var(--ht-primary)">
                    Vào quiz cuối buổi <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
