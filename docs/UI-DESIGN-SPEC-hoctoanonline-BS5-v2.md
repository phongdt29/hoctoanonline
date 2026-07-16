# UI DESIGN SPEC — HOCTOANONLINE.COM (BOOTSTRAP 5 EDITION)
## v2 — Phụ lục giao diện cho SPEC Laravel v3 · Bootstrap 5.3 CDN · KHÔNG build

> Concept giữ nguyên: **"Vở ô ly, mực tím — phiên bản hiện đại."**
> Thay đổi so với v1: dùng **Bootstrap 5.3 làm nền component chính** (card, button, badge, navbar, form, modal, toast...) và chỉ viết **1 file `theme.css` mỏng** để "nhuộm" Bootstrap theo nhận diện riêng — qua cơ chế CSS variables `--bs-*` của Bootstrap 5.3, không cần Sass/build.

---

## 1. NẠP THƯ VIỆN (layout Blade, thứ tự BẮT BUỘC)

```html
<!-- 1. Fonts (subset vietnamese) -->
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<!-- 2. Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- 3. Theme override — LUÔN đặt SAU bootstrap -->
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<!-- 4. Màu cá nhân hóa của student — SAU theme.css -->
<style>:root{ --ht-primary: {{ $student->favorite_color ?? '#5B4DFF' }}; }</style>

<!-- Cuối body -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
```
Icon: **Bootstrap Icons** (CDN `bootstrap-icons@1.11`) — đồng bộ hệ sinh thái, khỏi thêm Lucide.

---

## 2. `public/css/theme.css` — LỚP NHUỘM BOOTSTRAP

### 2.1. Token gốc (prefix `--ht-` để không đụng `--bs-`)
```css
:root{
  --ht-primary:      #5B4DFF;   /* tím mực — Blade override theo favorite_color */
  --ht-primary-ink:  #3F35C7;
  --ht-primary-soft: #EEEBFF;
  --ht-bg:           #F6F7FC;
  --ht-ink:          #1C2340;
  --ht-ink-soft:     #626C8A;
  --ht-line:         #E6E9F4;
  --ht-star:         #FFB020;
  --font-num:        'IBM Plex Mono', monospace;
}
```

### 2.2. Map vào biến Bootstrap (cách chuẩn 5.3, không cần Sass)
```css
:root{
  --bs-body-font-family: 'Be Vietnam Pro', system-ui, sans-serif;
  --bs-body-bg:    var(--ht-bg);
  --bs-body-color: var(--ht-ink);
  --bs-border-color: var(--ht-line);
  --bs-primary:    var(--ht-primary);
  --bs-primary-rgb: 91, 77, 255;          /* cho .text-primary, .bg-primary/opacity */
  --bs-link-color: var(--ht-primary);
  --bs-border-radius: .75rem;             /* 12px */
  --bs-border-radius-lg: 1rem;            /* 16px — card */
}

/* Nút primary: Bootstrap 5.3 cho override per-component qua --bs-btn-* */
.btn-primary{
  --bs-btn-bg: var(--ht-primary);
  --bs-btn-border-color: var(--ht-primary);
  --bs-btn-hover-bg: var(--ht-primary-ink);
  --bs-btn-hover-border-color: var(--ht-primary-ink);
  --bs-btn-active-bg: var(--ht-primary-ink);
  --bs-btn-focus-shadow-rgb: 91,77,255;
  font-weight: 600;
}
.btn-outline-primary{
  --bs-btn-color: var(--ht-primary);
  --bs-btn-border-color: var(--ht-line);
  --bs-btn-hover-bg: var(--ht-primary-soft);
  --bs-btn-hover-color: var(--ht-primary-ink);
  --bs-btn-hover-border-color: var(--ht-primary);
}

/* Card */
.card{
  --bs-card-border-color: var(--ht-line);
  --bs-card-border-radius: 1rem;
  box-shadow: 0 1px 2px rgba(28,35,64,.05), 0 8px 24px rgba(28,35,64,.06);
}

/* Nav pills (sidebar/tab) */
.nav-pills{ --bs-nav-pills-link-active-bg: var(--ht-primary-soft); }
.nav-pills .nav-link{ color: var(--ht-ink-soft); font-weight:600; }
.nav-pills .nav-link.active{ color: var(--ht-primary); }

/* Badge trạng thái — dùng chấm + chữ, không màu trần */
.badge{ font-weight:600; }
.badge .dot{ display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }

/* Progress */
.progress{ --bs-progress-bar-bg: var(--ht-primary); --bs-progress-height: .625rem;
           border-radius: 999px; }
```

### 2.3. Phần "chất riêng" (không có trong Bootstrap — giữ nguyên từ v1)
```css
/* SIGNATURE 1: nền giấy ô ly */
body{
  background-image:
    linear-gradient(rgba(91,77,255,.045) 1px, transparent 1px),
    linear-gradient(90deg, rgba(91,77,255,.045) 1px, transparent 1px);
  background-size: 24px 24px;
}

/* SIGNATURE 2: Mastery Grid — tiến độ kiểu "tô ô vở" */
.ht-mastery{ display:grid; grid-template-columns:repeat(auto-fill,minmax(15px,1fr)); gap:5px; }
.ht-mastery .cell{ aspect-ratio:1; border-radius:4px; border:1.5px solid var(--ht-line);
                   transition:transform .15s ease-out; }
.ht-mastery .cell.done{ background:var(--ht-primary); border-color:var(--ht-primary); }
.ht-mastery .cell.now{ background:var(--ht-primary-soft); border-color:var(--ht-primary); }
.ht-mastery .cell:hover{ transform:scale(1.25); }

/* Số liệu */
.num{ font-family: var(--font-num); }

/* Thanh mix 20/60/20 của recommendation */
.ht-mix{ display:flex; height:10px; border-radius:999px; overflow:hidden; }
.ht-mix .m-review{ background:#F5A524; } .ht-mix .m-new{ background:var(--ht-primary); }
.ht-mix .m-fix{ background:#12A150; }

@media (prefers-reduced-motion: reduce){ *{ transition:none!important; animation:none!important; } }
```
**Quy mô cho phép của theme.css: ≤ ~250 dòng.** Ngoài các mục trên, mọi thứ khác dùng class Bootstrap nguyên bản — cấm agent tự chế component trùng chức năng Bootstrap đã có.

---

## 3. MAP COMPONENT → BOOTSTRAP

| Nhu cầu | Dùng Bootstrap | Ghi chú |
|---|---|---|
| Thẻ nội dung | `.card` + `.card-body` | đã nhuộm ở §2.2 |
| Nút chính/phụ | `.btn-primary` / `.btn-outline-primary` | nhãn = hành động thật: "Bắt đầu học", "Nộp bài" |
| Sidebar & bottom tab | `.nav.nav-pills.flex-column` / `.navbar.fixed-bottom` | mobile <992px chuyển bottom tab |
| Chip streak/điểm | `.badge.rounded-pill.text-bg-light.border` | số dùng `.num` |
| Trạng thái điểm danh/risk | `.badge.rounded-pill` + `<span class="dot">` | 🟢 `#12A150` 🟡 `#F5A524` 🔴 `#DC3545` — luôn kèm chữ |
| Tiến độ % | `.progress` + `.progress-bar` | riêng lộ trình dùng `.ht-mastery` |
| Quiz option | `.list-group` + `.list-group-item-action` | chọn: thêm `.active`; sau nộp: `.list-group-item-success/-danger` |
| Toast (điểm ⭐, thông báo) | `.toast` + `bootstrap.Toast` | góc phải trên |
| Modal (confirm OCR, nộp bài) | `.modal` | dùng JS bundle sẵn |
| Form + validate lỗi | `.form-control` + `.invalid-feedback` | map lỗi 422 của Laravel vào `.is-invalid` |
| Bảng admin/teacher | `.table.table-hover.align-middle` | row 44px, không trang trí |
| Empty state | `.text-center.py-5` + icon `bi-*` + 1 nút | 1 câu hành động rõ |
| Tooltip/Popover | bundle sẵn | khởi tạo trong app.js |

**Blade components giữ nguyên danh sách** (`<x-card>`, `<x-stat>`, `<x-status-chip>`, `<x-mastery-grid>`, `<x-quiz-option>`, `<x-chat-bubble>`, `<x-empty-state>`) — nhưng bên trong render bằng markup Bootstrap ở bảng trên.

---

## 4. LAYOUT (Bootstrap grid)

- Khung: `.container-xxl` (max ~1320px), nội dung chính bọc `.row.g-4` (gap 24px).
- **Student desktop:** sidebar `.col-auto` cố định 92px (nav-pills dọc, icon + nhãn 11px) + `.col` nội dung. **Mobile:** ẩn sidebar (`d-none d-lg-flex`), hiện `.navbar.fixed-bottom` 5 tab (`d-lg-none`), padding-bottom cho body tránh che nội dung.
- **Parent:** không sidebar — `.container-lg`, 6 khối = 6 `.card` trong `.row.g-4` (`col-12 col-md-6`), khối risk đứng đầu `col-12`.
- **Teacher/Admin:** `.container-fluid` + bảng, mật độ cao.
- Topbar student: `.d-flex.justify-content-between` — chào + ngày | chips streak 🔥, điểm ⭐, avatar gia sư.

## 5. MOTION & QUALITY FLOOR (không đổi so với v1)

- Chỉ 1 khoảnh khắc "diễn": quiz ≥8 → ô Mastery Grid tô màu lan (scale 0→1, 400ms) + toast ⭐. Không confetti.
- Card vào trang: fade + rise 8px một lần. Tất cả bọc `prefers-reduced-motion`.
- Responsive tới 360px · vùng chạm ≥44px · focus dùng focus-ring mặc định Bootstrap (đã đổi màu theo `--bs-btn-focus-shadow-rgb`) · contrast ≥4.5:1 · bảng 10 màu personalization định sẵn, không cho nhập hex tự do · công thức toán: MathJax.

## 6. VIỆC CHO AI AGENT (thay ticket F5 trong Plan)

1. Tạo `public/css/theme.css` đúng §2 (≤250 dòng, không lặp lại thứ Bootstrap có sẵn).
2. Layout Blade §1 + §4: `layouts/student`, `layouts/parent`, `layouts/admin`, `layouts/auth`.
3. Blade components §3 render bằng markup Bootstrap.
4. `public/js/app.js`: CSRF setup, khởi tạo Toast/Tooltip, helper `showToast(msg, type)`.
5. Trang demo `/style-guide` (chỉ môi trường local) bày đủ mọi component để kiểm tra nhanh.

**DoD F5 (bản Bootstrap):** style-guide đủ component · responsive 360px · đổi `--ht-primary` một chỗ → toàn bộ nút/link/badge/progress đổi theo · Lighthouse a11y ≥ 90.
