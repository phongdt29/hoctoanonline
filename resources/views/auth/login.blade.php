@extends('layouts.auth')

@section('title', 'Đăng nhập')
@section('heading', 'Đăng nhập')
@section('subheading', 'Học toán cùng gia sư A.I của riêng bạn')

@section('form')
<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    {{-- Thong bao thanh cong (vd: vua doi mat khau xong) --}}
    @if (session('status'))
        <div class="alert alert-success py-2 small" role="alert">
            {{ session('status') }}
        </div>
    @endif

    {{-- Loi chung (sai email/mat khau, tai khoan bi khoa) --}}
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
               value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               autocomplete="email"
               required
               autofocus>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-baseline">
            <label for="password" class="form-label small fw-semibold">Mật khẩu</label>
            <a href="{{ route('password.request') }}" class="small">Quên mật khẩu?</a>
        </div>
        <input id="password"
               type="password"
               name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="current-password"
               required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
        <label class="form-check-label small" for="remember">Ghi nhớ đăng nhập</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 ht-tap">Đăng nhập</button>
</form>
@endsection

@section('footer-link')
    Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a>
@endsection
