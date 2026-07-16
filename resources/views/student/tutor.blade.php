@extends('layouts.student')

@section('title', 'Gia sư A.I')
@section('greeting', 'Gia sư A.I của bạn')
@php $active = 'tutor'; @endphp

@section('content')
<div class="mx-auto" style="max-width:720px">
    <x-card class="mb-3">
        <div id="tt-messages" class="d-flex flex-column gap-2" style="min-height:300px; max-height:60vh; overflow-y:auto">
            <x-chat-bubble sender="ai">Chào em! Có bài nào khó cứ hỏi nhé, mình cùng làm từng bước.</x-chat-bubble>
        </div>
    </x-card>

    <form id="tt-form" class="d-flex gap-2">
        <input id="tt-input" class="form-control" placeholder="Nhập câu hỏi..." autocomplete="off">
        <button class="btn btn-primary ht-tap" type="submit"><i class="bi bi-send"></i></button>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/tutor-chat.js') }}"></script>
@endpush
