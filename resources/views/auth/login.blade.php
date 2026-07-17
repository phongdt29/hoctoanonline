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

{{-- Tai khoan test — CHI hien khi KHONG phai production (khong lo tai khoan test that) --}}
@if (config('app.env') !== 'production')
    @php
        $testAccounts = [
            ['Học sinh (có lộ trình sẵn)', 'student1@hoctoan.test', 'password', 'bi-mortarboard', 'primary'],
            ['Học sinh mới (thử onboarding)', 'student2@hoctoan.test', 'password', 'bi-person-plus', 'primary'],
            ['Phụ huynh', 'parent1@hoctoan.test', 'password', 'bi-people', 'success'],
            ['Giáo viên', 'teacher1@hoctoan.test', 'password', 'bi-easel', 'warning'],
            ['Admin', 'admin@gmail.com', 'admin@', 'bi-shield-lock', 'danger'],
        ];
    @endphp
    <div class="mt-4 pt-3 border-top">
        <p class="small text-secondary mb-2">
            <i class="bi bi-flask"></i> Tài khoản test — bấm để đăng nhập nhanh
        </p>
        <div class="d-grid gap-2">
            @foreach ($testAccounts as [$label, $email, $pass, $icon, $color])
                <button type="button"
                        class="btn btn-sm btn-outline-{{ $color }} text-start ht-tap js-test-login"
                        data-email="{{ $email }}"
                        data-password="{{ $pass }}">
                    <i class="bi {{ $icon }}"></i> {{ $label }}
                    <span class="text-secondary d-block small">{{ $email }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.js-test-login').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('email').value = this.dataset.email;
                document.getElementById('password').value = this.dataset.password;
                this.closest('.card').querySelector('form').submit();
            });
        });
    </script>
    @endpush
@endif
@endsection

@section('footer-link')
    Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a>
@endsection
