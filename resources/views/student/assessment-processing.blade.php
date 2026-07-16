@extends('layouts.base')

@section('title', 'Đang xử lý')

@section('body')
<main class="container py-5">
    <div class="card mx-auto ht-rise text-center" style="max-width:520px">
        <div class="card-body p-5">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Đang xử lý</span>
            </div>
            <h1 class="h5 mb-2">A.I đang chấm bài và xây lộ trình</h1>
            <p class="text-secondary small mb-0">
                Việc này mất khoảng một phút. Trang sẽ tự cập nhật khi xong.
            </p>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    // Poll lai sau 5s cho toi khi cham xong (chuoi job Grade->Classify->Generate).
    setTimeout(function () { window.location.reload(); }, 5000);
</script>
@endpush
