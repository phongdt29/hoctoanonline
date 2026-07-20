{{--
    Layout goc — 3 layout con (student/parent/admin) va auth deu extend file nay.

    THU TU NAP LA BAT BUOC (UI-DESIGN-SPEC §1):
      1. Fonts  2. Bootstrap 5.3  3. theme.css (SAU bootstrap)  4. mau ca nhan (SAU theme.css)
    Doi thu tu -> theme khong ghi de duoc Bootstrap.
--}}
@php
    $theme = \App\Support\ThemeColor::resolve($themeColor ?? null);
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MathAI')</title>

    {{-- 1. Fonts (subset vietnamese) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

    {{-- 2. Bootstrap 5.3 + Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- 3. Theme override — LUON dat SAU bootstrap.
         ?v=filemtime: cache-bust — browser lay CSS moi ngay khi file doi (tranh giao dien trang do cache CSS cu). --}}
    <link href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}" rel="stylesheet">

    {{-- 4. Mau ca nhan hoa — SAU theme.css. Gia tri da qua ThemeColor::resolve()
         nen chac chan thuoc bang 10 mau, khong phai hex tu do. --}}
    <style>:root{ --ht-primary: {{ $theme['hex'] }}; --ht-primary-rgb: {{ $theme['rgb'] }}; }</style>

    @stack('head')
</head>
<body class="@yield('body-class')">

    @yield('body')

    {{-- Cuoi body, dung thu tu: jQuery -> bootstrap bundle -> MathJax -> app.js --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Cau hinh MathJax: PHAI dat truoc khi script tex-mml nap.
         Cho phep ca $...$ (soan toan tieng Viet quen dung) lan \(...\). --}}
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)'], ['$', '$']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
            },
            options: { skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'] },
        };
        // Helper: typeset lai 1 vung (dung cho xem truoc khi soan bai).
        window.htTypeset = function (el) {
            if (window.MathJax && window.MathJax.typesetPromise) {
                return window.MathJax.typesetPromise(el ? [el] : undefined);
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

    @stack('scripts')
</body>
</html>
