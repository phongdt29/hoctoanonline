@extends('layouts.admin')

@section('title', 'Lớp của tôi')
@section('page-title', 'Lớp của tôi')

@section('content')
@if ($classes->isEmpty())
    <x-empty-state icon="bi-easel" title="Chưa có lớp nào"
                   message="Liên hệ quản trị viên để được phân lớp." />
@else
    <div class="row g-3">
        @foreach ($classes as $class)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('teacher.class', $class) }}" class="text-decoration-none">
                    <div class="card ht-hover h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="ht-ico ht-ico-grad" style="width:46px;height:46px"><i class="bi bi-easel2"></i></span>
                                <div>
                                    <h2 class="h6 fw-bold mb-0 text-body">{{ $class->name }}</h2>
                                    <span class="text-secondary small">Khối {{ $class->grade }}</span>
                                </div>
                            </div>
                            <div class="d-flex gap-3 small text-secondary">
                                <span><i class="bi bi-people"></i> <span class="num">{{ $class->students_count }}</span> học sinh</span>
                                <span><i class="bi bi-journal-text"></i> <span class="num">{{ $class->assignments_count }}</span> bài tập</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
