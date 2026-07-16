@extends('layouts.student')

@section('title', 'Cá nhân')
@section('greeting', 'Trang cá nhân')
@php $active = 'profile'; @endphp

@section('content')
<div class="mx-auto" style="max-width:640px">
    <x-card title="Thông tin của bạn" icon="bi-person-badge" class="mb-4">
        <dl class="row small mb-0">
            <dt class="col-5 fw-normal text-secondary">Họ và tên</dt>
            <dd class="col-7">{{ $student->full_name }}</dd>
            <dt class="col-5 fw-normal text-secondary">Khối lớp</dt>
            <dd class="col-7">Lớp {{ $student->grade ?? '—' }}</dd>
            <dt class="col-5 fw-normal text-secondary">Trường</dt>
            <dd class="col-7">{{ $student->school_name ?? '—' }}</dd>
            <dt class="col-5 fw-normal text-secondary">Gia sư</dt>
            <dd class="col-7">{{ $student->tutor_gender === 'co' ? 'Cô' : 'Thầy' }}</dd>
            <dt class="col-5 fw-normal text-secondary">Điểm tích lũy</dt>
            <dd class="col-7"><span class="num">{{ $student->points_balance }}</span></dd>
            <dt class="col-5 fw-normal text-secondary">Chuỗi ngày học</dt>
            <dd class="col-7"><span class="num">{{ $student->streak_days }}</span> ngày</dd>
        </dl>
    </x-card>

    {{-- Ma moi phu huynh --}}
    <x-card title="Mã mời phụ huynh" icon="bi-people" class="mb-4">
        <p class="text-secondary small mb-2">Đưa mã này cho bố mẹ để bố mẹ theo dõi việc học của bạn.</p>
        <div class="d-flex align-items-center gap-2">
            <span class="num fs-4 fw-bold text-primary">{{ $student->invite_code ?? '—' }}</span>
        </div>
    </x-card>

    {{-- Huy hieu --}}
    <x-card title="Huy hiệu" icon="bi-award">
        <div id="pf-badges" class="d-flex flex-wrap gap-2">
            <span class="text-secondary small">Đang tải...</span>
        </div>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    window.ht.api.get('/api/v1/gamification/badges').then(function (r) {
        var $b = $('#pf-badges').empty();
        (r.data || []).forEach(function (badge) {
            var cls = badge.earned ? 'text-bg-primary' : 'text-bg-light border text-secondary';
            $b.append('<span class="badge rounded-pill ' + cls + '">' +
                (badge.earned ? '<i class="bi bi-award-fill"></i> ' : '') +
                $('<div>').text(badge.name).html() + '</span>');
        });
    });
});
</script>
@endpush
