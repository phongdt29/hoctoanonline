@extends('layouts.base')

@section('title', 'MathAI — Học toán cá nhân hóa bằng A.I')

@push('head')
<style>
    .hero-wrap { padding: 3rem 0 2.5rem; position: relative; overflow: hidden; }
    @media (min-width: 992px) { .hero-wrap { padding: 5rem 0 4rem; } }
    .hero-title { font-size: clamp(1.9rem, 4.5vw, 3.4rem); line-height: 1.12; font-weight: 800; letter-spacing: -.03em; }
    .hero-glow { position: absolute; width: 460px; height: 460px; border-radius: 50%;
        background: var(--ht-gradient); filter: blur(120px); opacity: .18; z-index: 0; }
    .step-num { width: 46px; height: 46px; border-radius: 14px; flex: none; font-size: 1.05rem;
        display: flex; align-items: center; justify-content: center; font-weight: 800;
        color: #fff; background: var(--ht-gradient); box-shadow: var(--ht-shadow-primary); }
    .float-card { animation: ht-float 5s ease-in-out infinite; }
    @keyframes ht-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @media (prefers-reduced-motion: reduce) { .float-card { animation: none; } }
    /* Menu mobile mo ra: panel gon, nut full-width */
    @media (max-width: 991.98px) {
        #navMain { padding: .25rem .25rem .75rem; border-top: 1px solid var(--ht-line); margin-top: .5rem; }
        #navMain .nav-link { padding: .55rem .75rem; border-radius: 12px; }
        #navMain .btn { width: 100%; }
    }
</style>
@endpush

@section('body')
{{-- Nav — hamburger gom gon tren mobile, ngang tren desktop --}}
<nav class="navbar navbar-expand-lg navbar-home sticky-top py-2">
    <div class="container">
        <x-brand size="md" class="navbar-brand" />

        <button class="navbar-toggler border-0 shadow-none p-1" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain"
                aria-controls="navMain" aria-expanded="false" aria-label="Mở menu">
            <i class="bi bi-list" style="font-size:1.8rem; color:var(--ht-primary)"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mt-2 mt-lg-0">
                <li class="nav-item"><a class="nav-link px-3" href="#tinh-nang">Tính năng</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#cho-ai">Dành cho ai</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#cach-hoat-dong">Cách hoạt động</a></li>
            </ul>
            <div class="d-flex flex-column flex-lg-row gap-2 ms-lg-3 mt-3 mt-lg-0">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary">Vào học</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Đăng ký miễn phí</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- Hero --}}
<header class="hero-wrap">
    <div class="hero-glow" style="top:-140px; left:-80px"></div>
    <div class="hero-glow" style="bottom:-200px; right:-120px; opacity:.12"></div>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 ht-rise">
                <span class="badge rounded-pill mb-3 px-3 py-2" style="background:var(--ht-primary-soft); color:var(--ht-primary)">
                    <i class="bi bi-stars"></i> Cá nhân hóa bằng trí tuệ nhân tạo
                </span>
                <h1 class="hero-title mb-3">
                    Học toán theo <span class="ht-text-grad">lộ trình riêng</span> của chính bạn
                </h1>
                <p class="fs-5 text-secondary mb-4" style="max-width:34rem">
                    A.I đánh giá đúng năng lực thật, xây giáo trình riêng cho từng học sinh lớp 1–12,
                    và đồng hành từng buổi như một gia sư của riêng em.
                </p>
                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mb-4">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg ht-tap">
                        Bắt đầu miễn phí <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="#cach-hoat-dong" class="btn btn-outline-primary btn-lg ht-tap">Xem cách hoạt động</a>
                </div>
                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 gap-sm-4 text-secondary small">
                    <span><i class="bi bi-check-circle-fill text-success"></i> Không cần thẻ tín dụng</span>
                    <span><i class="bi bi-check-circle-fill text-success"></i> Gia sư A.I 24/7</span>
                    <span><i class="bi bi-check-circle-fill text-success"></i> Phụ huynh theo dõi được</span>
                </div>
            </div>
            <div class="col-lg-6 ht-rise ht-rise-2">
                {{-- Mockup dashboard --}}
                <div class="card float-card" style="box-shadow:var(--ht-shadow-lg)">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Lộ trình của bạn</span>
                            <span class="badge rounded-pill" style="background:var(--ht-primary-soft);color:var(--ht-primary)"><span class="num">72</span>% hoàn thành</span>
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
<section id="tinh-nang" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge rounded-pill px-3 py-2 mb-3 fw-semibold"
                  style="background:color-mix(in srgb, var(--ht-primary) 12%, #fff); color:var(--ht-primary)">
                <i class="bi bi-lightbulb"></i> Tính năng nổi bật
            </span>
            <h2 class="display-6 fw-bold mb-2">Mọi thứ một học sinh cần để <span class="ht-text-grad">tiến bộ thật</span></h2>
            <p class="text-secondary fs-5 mb-0">Không phải web bài tập thông thường — đây là gia sư A.I hiểu bạn.</p>
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
                    <div class="card ht-hover h-100">
                        <div class="card-body p-4">
                            <div class="ht-ico mb-3"><i class="bi {{ $icon }}"></i></div>
                            <h3 class="h6 fw-bold">{{ $title }}</h3>
                            <p class="text-secondary small mb-0">{{ $desc }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Danh sach tinh nang day du — chia theo doi tuong --}}
<section id="cho-ai" class="py-5 position-relative overflow-hidden"
         style="background:linear-gradient(180deg, color-mix(in srgb, var(--ht-primary) 7%, #fff) 0%, var(--ht-bg) 55%)">
    <div class="hero-glow" style="top:-150px; right:-90px"></div>
    <div class="hero-glow" style="bottom:-190px; left:-120px; opacity:.10"></div>
    <div class="container position-relative">
        <div class="text-center mb-5">
            <span class="badge rounded-pill px-3 py-2 mb-3 fw-semibold"
                  style="background:color-mix(in srgb, var(--ht-primary) 12%, #fff); color:var(--ht-primary)">
                <i class="bi bi-stars"></i> Tất cả trong một nền tảng
            </span>
            <h2 class="display-6 fw-bold mb-2">Đầy đủ tính năng cho <span class="ht-text-grad">cả nhà và nhà trường</span></h2>
            <p class="text-secondary fs-5 mb-0">Một nền tảng — học sinh học, phụ huynh theo dõi, giáo viên soạn giảng.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['bi-mortarboard', 'Học sinh', [
                    'Bài kiểm tra đầu vào chấm bằng A.I',
                    'Giáo trình cá nhân hoá 4 giai đoạn (lớp 1–12)',
                    'Học theo buổi: lý thuyết · bài tập · quiz',
                    'Gia sư A.I hỏi đáp, giải từng bước',
                    'Giải bài từ ảnh (chụp đề toán)',
                    'Điểm thưởng, huy hiệu, chuỗi ngày học',
                    'Làm đề trắc nghiệm, chấm điểm ngay',
                ]],
                ['bi-people', 'Phụ huynh', [
                    'Theo dõi thời gian học thật của con',
                    'Đèn tín hiệu xanh / vàng / đỏ dễ hiểu',
                    'Cảnh báo sớm khi con sa sút',
                    'Liên kết nhiều con bằng mã mời',
                    'Báo cáo tiến độ định kỳ',
                    'Đồng hành mà không cần rành toán',
                ]],
                ['bi-easel', 'Giáo viên & Nhà trường', [
                    'Soạn bài & soạn đề bằng A.I',
                    'Bàn phím công thức toán, chèn LaTeX',
                    'Tạo đề trắc nghiệm nhiều mã đề + chấm tự động',
                    'Lên giáo trình bằng A.I, gán cho học sinh',
                    'In / xuất PDF tài liệu, đề, đáp án',
                    'Quản lý lớp, giao & chấm bài tập',
                ]],
            ] as [$icon, $group, $items])
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 ht-hover"
                         style="border-radius:var(--ht-radius); box-shadow:var(--ht-shadow-lg); overflow:hidden">
                        <div style="height:5px; background:var(--ht-gradient)"></div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="ht-ico ht-ico-grad" style="width:52px; height:52px; font-size:1.4rem"><i class="bi {{ $icon }}"></i></div>
                                <h3 class="h5 fw-bold mb-0">{{ $group }}</h3>
                            </div>
                            <ul class="list-unstyled mb-0 d-grid gap-2">
                                @foreach ($items as $item)
                                    <li class="d-flex gap-2 small align-items-start">
                                        <i class="bi bi-check-circle-fill flex-shrink-0" style="color:var(--ht-primary); margin-top:2px"></i>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Cách hoạt động --}}
<section id="cach-hoat-dong" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge rounded-pill px-3 py-2 mb-3 fw-semibold"
                  style="background:color-mix(in srgb, var(--ht-primary) 12%, #fff); color:var(--ht-primary)">
                <i class="bi bi-rocket-takeoff"></i> Bắt đầu dễ dàng
            </span>
            <h2 class="display-6 fw-bold mb-2">Chỉ <span class="ht-text-grad">4 bước</span> để bắt đầu</h2>
            <p class="text-secondary fs-5 mb-0">Từ đăng ký đến buổi học đầu tiên — mọi thứ tự động.</p>
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
                            <h3 class="h6 fw-bold mb-1">{{ $title }}</h3>
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
        <div class="card border-0 text-center overflow-hidden" style="background:var(--ht-gradient); color:#fff; box-shadow:var(--ht-shadow-lg)">
            <div class="card-body p-5">
                <h2 class="display-6 fw-bold mb-2">Sẵn sàng học toán theo cách của riêng bạn?</h2>
                <p class="mb-4 opacity-75">Miễn phí bắt đầu. Không cần thẻ. Có ngay lộ trình sau bài test đầu vào.</p>
                <a href="{{ route('register') }}" class="btn btn-light btn-lg ht-tap fw-bold px-4" style="color:var(--ht-primary)">
                    Đăng ký miễn phí ngay <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="py-4 border-top mt-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2 text-secondary small">
        <span>© {{ now()->year }} MathAI — Nền tảng học toán cá nhân hóa bằng A.I</span>
        <div class="d-flex gap-3">
            <a href="{{ route('login') }}" class="text-secondary">Đăng nhập</a>
            <a href="{{ route('register') }}" class="text-secondary">Đăng ký</a>
        </div>
    </div>
</footer>
@endsection
