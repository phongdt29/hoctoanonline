@extends('layouts.base')

@section('title', 'hoctoanonline — Học toán cá nhân hóa bằng A.I')

@push('head')
<style>
    .hero-wrap { padding: 4rem 0 3rem; }
    .hero-title { font-size: clamp(1.9rem, 4vw, 3rem); line-height: 1.15; font-weight: 800; }
    .feature-ico {
        width: 52px; height: 52px; border-radius: 14px;
        background: var(--ht-primary-soft); color: var(--ht-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .step-num {
        width: 40px; height: 40px; border-radius: 50%; flex: none;
        background: var(--ht-primary); color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: 700;
    }
    .navbar-home { backdrop-filter: blur(8px); background: rgba(255,255,255,.8); }
</style>
@endpush

@section('body')
{{-- Nav --}}
<nav class="navbar navbar-expand-lg navbar-home sticky-top border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <span class="feature-ico" style="width:36px;height:36px;font-size:1rem"><i class="bi bi-calculator-fill"></i></span>
            hoctoanonline
        </a>
        <div class="d-flex gap-2">
            @auth
                <a href="{{ route('home') }}" class="btn btn-primary ht-tap">Vào học</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary ht-tap">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-primary ht-tap">Đăng ký miễn phí</a>
            @endauth
        </div>
    </div>
</nav>

{{-- Hero --}}
<header class="hero-wrap">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge rounded-pill text-bg-light border mb-3">
                    <i class="bi bi-stars text-primary"></i> Cá nhân hóa bằng trí tuệ nhân tạo
                </span>
                <h1 class="hero-title mb-3">
                    Học toán theo <span class="text-primary">lộ trình riêng</span> của chính bạn
                </h1>
                <p class="fs-5 text-secondary mb-4">
                    A.I đánh giá đúng năng lực thật, xây giáo trình riêng cho từng học sinh lớp 6–12,
                    và đồng hành từng buổi như một gia sư của riêng em.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg ht-tap">
                        Bắt đầu miễn phí <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="#cach-hoat-dong" class="btn btn-outline-primary btn-lg ht-tap">Xem cách hoạt động</a>
                </div>
                <div class="d-flex flex-wrap gap-4 text-secondary small">
                    <span><i class="bi bi-check-circle-fill text-success"></i> Không cần thẻ tín dụng</span>
                    <span><i class="bi bi-check-circle-fill text-success"></i> Có gia sư A.I 24/7</span>
                    <span><i class="bi bi-check-circle-fill text-success"></i> Phụ huynh theo dõi được</span>
                </div>
            </div>
            <div class="col-lg-6">
                {{-- Mockup dashboard nho --}}
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold">Lộ trình của bạn</span>
                            <span class="badge rounded-pill text-bg-light border"><span class="num">72</span>% hoàn thành</span>
                        </div>
                        <div class="ht-mastery mb-3">
                            @for ($i = 0; $i < 40; $i++)
                                <div class="cell {{ $i < 26 ? 'done' : ($i === 26 ? 'now' : '') }}"></div>
                            @endfor
                        </div>
                        <div class="ht-mix mb-2">
                            <div class="m-review" style="width:20%"></div>
                            <div class="m-new" style="width:60%"></div>
                            <div class="m-fix" style="width:20%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary">
                            <span>Ôn <span class="num">20%</span></span>
                            <span>Bài mới <span class="num">60%</span></span>
                            <span>Củng cố <span class="num">20%</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Tính năng --}}
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 fw-bold">Mọi thứ một học sinh cần để tiến bộ thật</h2>
            <p class="text-secondary">Không phải web bài tập thông thường — đây là gia sư A.I hiểu bạn.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['bi-clipboard-data', 'Đánh giá đúng năng lực', 'A.I chấm bài test đầu vào theo nhiều tín hiệu, không chỉ điểm số — phân loại đúng cả khi điểm cao mà hổng nền.'],
                ['bi-map', 'Giáo trình cá nhân hóa', 'Lộ trình 4 giai đoạn sinh riêng cho bạn, ưu tiên đúng chủ đề đang yếu.'],
                ['bi-chat-dots', 'Gia sư A.I thầy/cô', 'Hỏi đáp từng bước, giải thích dễ hiểu theo đúng trình độ và phong cách bạn chọn.'],
                ['bi-camera', 'Giải bài từ ảnh', 'Chụp đề toán, A.I đọc và gợi mở cách làm — không cho đáp án ngay để bạn tự tư duy.'],
                ['bi-graph-up-arrow', 'Gợi ý học thích nghi', 'Mỗi buổi học điều chỉnh theo hành vi thật của bạn, không lặp lại lộ trình cứng.'],
                ['bi-shield-check', 'Phụ huynh đồng hành', 'Theo dõi thời gian học thật, cảnh báo sớm, đèn tín hiệu xanh/vàng/đỏ dễ hiểu.'],
            ] as [$icon, $title, $desc])
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="feature-ico mb-3"><i class="bi {{ $icon }}"></i></div>
                            <h3 class="h6 fw-semibold">{{ $title }}</h3>
                            <p class="text-secondary small mb-0">{{ $desc }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Cách hoạt động --}}
<section id="cach-hoat-dong" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 fw-bold">Chỉ 4 bước để bắt đầu</h2>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach ([
                ['Đăng ký & tạo hồ sơ', 'Nhập vài thông tin để A.I hiểu bạn: khối lớp, học lực, sở thích.'],
                ['Làm bài kiểm tra đầu vào', 'A.I ra đề vừa sức, phân tích điểm mạnh yếu theo từng chủ đề.'],
                ['Nhận lộ trình riêng', 'Giáo trình cá nhân hóa sinh tự động, bắt đầu từ chỗ bạn cần củng cố.'],
                ['Học & tiến bộ mỗi ngày', 'Học bài, làm quiz, hỏi gia sư A.I — lộ trình tự điều chỉnh theo bạn.'],
            ] as $i => [$title, $desc])
                <div class="col-md-6 col-lg-3">
                    <div class="d-flex gap-3">
                        <div class="step-num num">{{ $i + 1 }}</div>
                        <div>
                            <h3 class="h6 fw-semibold mb-1">{{ $title }}</h3>
                            <p class="text-secondary small mb-0">{{ $desc }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-5">
    <div class="container">
        <div class="card border-0" style="background: var(--ht-primary); color: #fff">
            <div class="card-body p-5 text-center">
                <h2 class="h3 fw-bold mb-2">Sẵn sàng học toán theo cách của riêng bạn?</h2>
                <p class="mb-4 opacity-75">Miễn phí bắt đầu. Không cần thẻ. Có ngay lộ trình sau bài test đầu vào.</p>
                <a href="{{ route('register') }}" class="btn btn-light btn-lg ht-tap fw-semibold">
                    Đăng ký miễn phí ngay
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="py-4 border-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2 text-secondary small">
        <span>© {{ now()->year }} hoctoanonline — Nền tảng học toán cá nhân hóa bằng A.I</span>
        <div class="d-flex gap-3">
            <a href="{{ route('login') }}" class="text-secondary">Đăng nhập</a>
            <a href="{{ route('register') }}" class="text-secondary">Đăng ký</a>
        </div>
    </div>
</footer>
@endsection
