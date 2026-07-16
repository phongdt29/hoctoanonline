@extends('layouts.student')

@section('title', 'Giải bài')
@section('greeting', 'Giải bài cùng A.I')
@php $active = 'solver'; @endphp

@section('content')
<div class="mx-auto" style="max-width:720px">
    <x-card title="Nhập đề toán" icon="bi-pencil-square" class="mb-4">
        <p class="text-secondary small">A.I sẽ <strong>gợi mở</strong> cách làm trước — không cho đáp án ngay để bạn tự tư duy.</p>

        <div class="mb-3">
            <textarea id="sv-problem" class="form-control" rows="3" placeholder="VD: Tính 1/2 + 1/3"></textarea>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button id="sv-text" class="btn btn-primary ht-tap">Nhờ gợi ý</button>
            <label class="btn btn-outline-primary ht-tap mb-0">
                <i class="bi bi-camera"></i> Chụp ảnh đề
                <input id="sv-image" type="file" accept="image/*" hidden>
            </label>
        </div>
    </x-card>

    {{-- Ket qua --}}
    <div id="sv-result" class="d-none">
        <x-card title="Gợi ý" icon="bi-lightbulb" class="mb-3">
            <div id="sv-parsed" class="alert alert-light border small d-none"></div>
            <div id="sv-hint" class="mb-3"></div>
            <div class="d-flex gap-2 flex-wrap">
                <button id="sv-more" class="btn btn-sm btn-outline-primary ht-tap">Gợi ý thêm</button>
                <button id="sv-full" class="btn btn-sm btn-outline-primary ht-tap">Xem lời giải</button>
                <button id="sv-similar" class="btn btn-sm btn-outline-primary ht-tap">Bài tương tự</button>
            </div>
        </x-card>

        <x-card id="sv-solution-card" title="Lời giải" icon="bi-check2-circle" class="mb-3 d-none">
            <div id="sv-solution"></div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/solver.js') }}"></script>
@endpush
