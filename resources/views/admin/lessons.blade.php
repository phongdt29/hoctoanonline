@extends('layouts.admin')

@section('title', 'Soạn bài')
@section('page-title', 'Soạn bài — nội dung & công thức toán')

@section('page-actions')
    <a href="{{ route('admin.home') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Về báo cáo</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<x-card class="mb-3">
    <form method="GET" class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control form-control-sm"
               placeholder="Tìm bài học theo tiêu đề…">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Tìm</button>
        @if ($q !== '')
            <a href="{{ route('admin.lessons') }}" class="btn btn-sm btn-outline-secondary">Xoá lọc</a>
        @endif
    </form>
</x-card>

<x-card>
    @if ($lessons->isEmpty())
        <x-empty-state icon="bi-journal-x" title="Không có bài học nào">
            {{ $q !== '' ? 'Không khớp từ khoá “' . $q . '”.' : 'Chưa có bài học trong hệ thống.' }}
        </x-empty-state>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr class="small text-secondary">
                    <th style="width:70px">#</th><th>Tiêu đề</th><th>Học sinh</th><th style="width:120px"></th>
                </tr></thead>
                <tbody>
                    @foreach ($lessons as $lesson)
                        <tr>
                            <td class="num text-secondary">{{ $lesson->id }}</td>
                            <td class="fw-semibold">{{ $lesson->title }}</td>
                            <td class="small text-secondary">
                                {{ $lesson->module?->curriculum?->student?->full_name ?? '—' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i> Soạn
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $lessons->links('pagination::bootstrap-5') }}</div>
    @endif
</x-card>
@endsection
