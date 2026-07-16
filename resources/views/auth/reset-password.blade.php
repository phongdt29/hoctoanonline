@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@section('heading', 'Đặt mật khẩu mới')
@section('subheading', 'Chọn mật khẩu từ 8 ký tự trở lên')

@section('form')
<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    @if ($errors->has('email'))
        <div class="alert alert-danger py-2 small" role="alert">
            {{ $errors->first('email') }}
        </div>
    @endif

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold">Email</label>
        <input id="email"
               type="email"
               name="email"
               value="{{ old('email', $email) }}"
               class="form-control"
               autocomplete="email"
               required
               readonly>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Mật khẩu mới</label>
        <input id="password"
               type="password"
               name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password"
               required
               autofocus>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label small fw-semibold">Nhập lại mật khẩu mới</label>
        <input id="password_confirmation"
               type="password"
               name="password_confirmation"
               class="form-control"
               autocomplete="new-password"
               required>
    </div>

    <button type="submit" class="btn btn-primary w-100 ht-tap">Đổi mật khẩu</button>
</form>
@endsection

@section('footer-link')
    <a href="{{ route('login') }}">Quay lại đăng nhập</a>
@endsection
