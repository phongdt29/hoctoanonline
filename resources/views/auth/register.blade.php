@extends('layouts.auth')

@section('title', 'Đăng ký')
@section('heading', 'Tạo tài khoản')
@section('subheading', 'Vài bước nữa là bắt đầu học được rồi')

@section('form')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label small fw-semibold">Họ và tên</label>
        <input id="name"
               type="text"
               name="name"
               value="{{ old('name') }}"
               class="form-control @error('name') is-invalid @enderror"
               autocomplete="name"
               required
               autofocus>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold">Email</label>
        <input id="email"
               type="email"
               name="email"
               value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               autocomplete="email"
               required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Mật khẩu</label>
        <input id="password"
               type="password"
               name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password"
               required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Từ 8 ký tự trở lên.</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label small fw-semibold">Nhập lại mật khẩu</label>
        <input id="password_confirmation"
               type="password"
               name="password_confirmation"
               class="form-control"
               autocomplete="new-password"
               required>
    </div>

    <fieldset class="mb-4">
        <legend class="form-label small fw-semibold">Bạn là</legend>

        <div class="d-flex gap-2">
            @foreach ([
                'student' => ['Học sinh', 'bi-mortarboard'],
                'parent'  => ['Phụ huynh', 'bi-people'],
            ] as $value => [$label, $icon])
                <input type="radio"
                       class="btn-check"
                       name="role"
                       id="role-{{ $value }}"
                       value="{{ $value }}"
                       @checked(old('role', 'student') === $value)
                       required>
                <label class="btn btn-outline-primary flex-fill ht-tap d-flex flex-column justify-content-center"
                       for="role-{{ $value }}">
                    <i class="bi {{ $icon }} fs-5" aria-hidden="true"></i>
                    <span class="small">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        @error('role')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </fieldset>

    <button type="submit" class="btn btn-primary w-100 ht-tap">Tạo tài khoản</button>
</form>
@endsection

@section('footer-link')
    Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a>
@endsection
