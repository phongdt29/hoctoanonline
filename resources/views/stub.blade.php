{{--
    Trang tam cho cac dich redirect cua A1. Moi trang se duoc thay bang trang that
    o dung ticket ghi ben duoi. Ton tai de DoD A1 (role redirect) kiem chung duoc.
--}}
@extends('layouts.base')

@section('title', $title)

@section('body')
<main class="container py-5">
    <div class="card mx-auto ht-rise" style="max-width:560px">
        <div class="card-body p-4">
            <h1 class="h5 mb-1">{{ $title }}</h1>
            <p class="text-secondary small mb-3">Trang này sẽ được dựng ở ticket <strong>{{ $ticket }}</strong>.</p>

            <dl class="row small mb-4">
                <dt class="col-4 fw-normal text-secondary">Đăng nhập bằng</dt>
                <dd class="col-8">{{ auth()->user()?->email ?? '—' }}</dd>
                <dt class="col-4 fw-normal text-secondary">Vai trò</dt>
                <dd class="col-8"><span class="badge rounded-pill text-bg-light border">{{ auth()->user()?->role ?? '—' }}</span></dd>
            </dl>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm ht-tap">Đăng xuất</button>
            </form>
        </div>
    </div>
</main>
@endsection
