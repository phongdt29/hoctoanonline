<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>hoctoanonline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width:640px">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">hoctoanonline</h1>
                <p class="text-muted mb-4">Nền tảng học toán online cá nhân hóa bằng A.I.</p>
                <dl class="row small mb-0">
                    <dt class="col-5 fw-normal text-muted">Laravel</dt>
                    <dd class="col-7">{{ app()->version() }}</dd>
                    <dt class="col-5 fw-normal text-muted">PHP</dt>
                    <dd class="col-7">{{ PHP_VERSION }}</dd>
                    <dt class="col-5 fw-normal text-muted">Thời lượng quiz</dt>
                    <dd class="col-7 mb-0">{{ config('hoctoan.quiz.duration_minutes') }} phút</dd>
                </dl>
            </div>
        </div>
        <p class="text-center text-muted small mt-3 mb-0">Trang tạm — layout thật dựng ở ticket F5.</p>
    </div>
</body>
</html>
