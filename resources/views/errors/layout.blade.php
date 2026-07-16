{{-- Ticket P4 — layout trang loi theo theme (nen o ly, the trang). --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · hoctoanonline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
</head>
<body>
<main class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card text-center" style="max-width:460px">
        <div class="card-body p-5">
            <div class="num fw-bold text-primary" style="font-size:3rem">@yield('code')</div>
            <h1 class="h5 mt-2 mb-2">@yield('title')</h1>
            <p class="text-secondary small mb-4">@yield('message')</p>
            <a href="{{ url('/') }}" class="btn btn-primary ht-tap">Về trang chủ</a>
        </div>
    </div>
</main>
</body>
</html>
