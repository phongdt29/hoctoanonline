{{--
    Layout auth (ticket A4): the trang bo goc lon, logo gradient, nut primary tim muc.
    Khong ca nhan hoa mau — chua dang nhap thi chua biet favorite_color.
--}}
@extends('layouts.base')

@section('body')
<main class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="w-100 ht-rise" style="max-width:440px">
        <div class="text-center mb-4">
            <x-brand size="lg" class="mb-3" />
            <h1 class="h4 mb-1">@yield('heading', 'Chào mừng')</h1>
            <p class="text-secondary small mb-0">@yield('subheading')</p>
        </div>

        <div class="card" style="box-shadow:var(--ht-shadow-lg)">
            <div class="card-body p-4 p-sm-5">
                @yield('form')
            </div>
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">@yield('footer-link')</p>
    </div>
</main>
@endsection
