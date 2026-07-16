{{--
    Layout auth (ticket A4): nen o ly, the trang bo 16px, nut primary tim muc.
    Khong ca nhan hoa mau — chua dang nhap thi chua biet favorite_color.
--}}
@extends('layouts.base')

@section('body')
<main class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="w-100" style="max-width:420px">
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">@yield('heading', 'hoctoanonline')</h1>
            <p class="text-secondary small mb-0">@yield('subheading')</p>
        </div>

        <div class="card ht-rise">
            <div class="card-body p-4">
                @yield('form')
            </div>
        </div>

        <p class="text-center text-secondary small mt-3 mb-0">@yield('footer-link')</p>
    </div>
</main>
@endsection
