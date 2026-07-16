@extends('layouts.auth')

@section('title', 'Quên mật khẩu')
@section('heading', 'Quên mật khẩu')
@section('subheading', 'Nhập email, chúng tôi gửi link đặt lại cho bạn')

@section('form')
@if (session('status'))
    <div class="alert alert-success py-2 small" role="alert">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label small fw-semibold">Email</label>
        <input id="email"
               type="email"
               name="email"
               value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               autocomplete="email"
               required
               autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Link có hiệu lực {{ config('hoctoan.reset_token_ttl_min') }} phút.</div>
    </div>

    <button type="submit" class="btn btn-primary w-100 ht-tap">Gửi link đặt lại</button>
</form>
@endsection

@section('footer-link')
    Nhớ ra rồi? <a href="{{ route('login') }}">Đăng nhập</a>
@endsection
