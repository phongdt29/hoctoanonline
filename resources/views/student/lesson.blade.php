@extends('layouts.student')

@section('title', $lesson->title)
@section('greeting', $lesson->title)
@php $active = 'curriculum'; @endphp

@section('content')
<div class="mx-auto" style="max-width:760px">

    {{-- Tieu de + nut in (nut an khi in) --}}
    <div class="print-title d-none">{{ $lesson->title }}</div>
    <div class="d-flex justify-content-end mb-3">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer"></i> In bài học
        </button>
    </div>

    {{-- Ly thuyet --}}
    <x-card title="Lý thuyết" icon="bi-book" class="mb-4">
        <div class="lesson-theory">{!! nl2br(e($lesson->theory_content)) !!}</div>
    </x-card>

    {{-- Bai tap 3 muc --}}
    <x-card title="Bài tập" icon="bi-pencil-square" class="mb-4">
        @php
            $byDiff = $lesson->exercises->groupBy('difficulty');
            $diffLabel = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó'];
        @endphp

        @foreach (['easy', 'medium', 'hard'] as $diff)
            @if ($byDiff->has($diff))
                <h3 class="h6 text-secondary mt-3 mb-2">{{ $diffLabel[$diff] }}</h3>
                @foreach ($byDiff->get($diff) as $ex)
                    <div class="border rounded-3 p-3 mb-2">
                        <div>{{ $ex->content }}</div>
                    </div>
                @endforeach
            @endif
        @endforeach
    </x-card>

    {{-- Vao quiz --}}
    @if ($lesson->quiz)
        <div class="text-center">
            <p class="text-secondary small mb-2">
                Làm xong bài tập? Kiểm tra 15 phút để mở buổi tiếp theo.
            </p>
            <a href="{{ route('quiz.show', $lesson->quiz) }}" class="btn btn-primary btn-lg ht-tap">
                Vào quiz cuối buổi
            </a>
        </div>
    @endif
</div>
@endsection
