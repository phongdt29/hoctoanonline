# CLAUDE.md — hoctoanonline.com

Nền tảng học toán online cá nhân hóa bằng AI cho học sinh lớp 6–12 (Việt Nam).

## Nguồn sự thật (đọc trước khi code)

1. `docs/SPEC-AI-CODING-hoctoanonline-Laravel-v3.md` — schema, business rules, API
2. `docs/UI-DESIGN-SPEC-hoctoanonline-BS5-v2.md` — giao diện (Bootstrap 5 + theme.css)
3. `docs/PLAN-CODE-TOAN-TRINH-hoctoanonline.md` — ticket, thứ tự làm, DoD

Khi spec và code hiện tại mâu thuẫn → spec thắng. Khi spec thiếu → hỏi lại, đừng tự bịa rule nghiệp vụ.

## Stack (KHÔNG đổi, không thêm)

- Laravel 12 · PHP 8.3 · MySQL 8 (utf8mb4_unicode_ci) · Redis (queue) · Eloquent

> **Vì sao Laravel 12 chứ không phải 11 như spec gốc:** Composer **từ chối cài** Laravel 11 — toàn bộ v11.0.0→v11.55.0 dính security advisory chưa vá (hết hạn hỗ trợ bảo mật). Laravel 13 thì đòi `php ^8.3`, trong khi XAMPP đang PHP 8.2.12 và nâng PHP sẽ ảnh hưởng ~25 project khác trong `htdocs`. Laravel 12 đòi `php ^8.2` → khớp máy, còn hỗ trợ bảo mật. Đã cài: **Laravel 12.64.0**.
- Frontend: Blade + jQuery 3 + Bootstrap 5.3 qua CDN. **CẤM Vite/webpack/npm build, CẤM React/Vue/Tailwind.**
- Test: Pest. Queue worker qua Supervisor. Cron duy nhất: `schedule:run`.

### Local dev (XAMPP/Windows) — khác production, chỉ ở tầng hạ tầng

Stack ứng dụng **không đổi**. Chỉ driver hạ tầng khác, khai báo qua `.env`, không đụng vào code:

| | Production (VPS) | Local dev |
|---|---|---|
| Queue | `redis` | `QUEUE_CONNECTION=database` |
| Cache | `redis` | `CACHE_STORE=file` |
| DB | MySQL 8 | MariaDB 10.4 (XAMPP) |
| PHP | 8.3 | 8.2.12 (Laravel 11 chấp nhận ≥8.2) |

Kéo theo: local cần `php artisan queue:table && php artisan migrate`. **Không cài Redis trên máy dev.**

⚠️ Code phải viết **driver-agnostic** — Job/cache không được giả định Redis. Đây là lý do cấm gọi thẳng facade `Redis::`.

⚠️ MariaDB 10.4 **không có kiểu `JSON` thật** (chỉ là bí danh `LONGTEXT`): không index/CHECK được trên cột JSON. Nếu cần lọc theo field bên trong JSON → tách ra cột riêng, đừng dựa vào JSON functions.

⚠️ Extension `gd` **chưa bật** trong `c:\xampp\php\php.ini` — cần cho Solver ảnh (OCR). Bỏ `;` trước `extension=gd`, restart Apache.

## Quy tắc bắt buộc (vi phạm = ticket fail)

1. **Không hardcode ngưỡng nghiệp vụ.** Mọi con số (70%, 15 phút, max 2 hint, trọng số risk...) đọc từ `config/hoctoan.php`.
2. **Mọi endpoint:** FormRequest validation → Policy (RBAC) → Service → audit log. Business logic nằm trong `app/Services/`, không nằm trong Controller.
3. **Mọi call AI** đi qua `AiProviderService` (failover theo priority) và ghi `ai_logs`. Không gọi HTTP AI trực tiếp từ nơi khác.
4. **Call AI lâu** (tạo đề, chấm tự luận, generate curriculum) chạy trong Job (`tries=3`), không chạy trong request. Queue driver lấy từ `.env` (prod redis · local database) — **không hardcode connection trong code**.
5. **`point_ledger` là append-only** — không viết bất kỳ code path update/delete nào.
6. **Solver không bao giờ trả đáp án cuối ở lần gọi đầu.** Thứ tự: hint mở → hint sâu (max 2) → full solution khi người dùng bấm.
7. **Quiz timer và anti-cheat quyết định ở server** (`expires_at`); client chỉ hiển thị.
8. **Giao diện:** chỉ dùng class Bootstrap 5 + token trong `public/css/theme.css` (prefix `--ht-`). Không tự chế màu/hex mới, không viết CSS trùng chức năng Bootstrap đã có. Số liệu (điểm, %, timer) luôn có class `.num`. Trạng thái (present/risk) luôn là chấm màu + chữ, không màu trần.
9. **Tiếng Việt cho người dùng, tiếng Anh cho code.** UI copy sentence case, nhãn nút = hành động thật ("Bắt đầu học", "Nộp bài"). Text người dùng thấy đặt trong `lang/vi/`.
10. **Mỗi ticket phải có Pest test** cho business rule của nó. Xong ticket: chạy `php artisan test`, tự đối chiếu DoD trong Plan, báo rõ mục nào chưa đạt.

## Naming & cấu trúc

- Bảng `snake_case` số nhiều · Model `PascalCase` số ít · enum value `snake_case` ('trung_binh', 'absent_pending')
- API AJAX: prefix `/api/v1/`, kebab-case, trả JSON `{ data, message }`; lỗi validate 422 chuẩn Laravel
- Services: `ClassificationService`, `CurriculumService`, `RecommendationService`, `SolverService`, `AttendanceService`, `RiskScoreService`, `PointLedgerService`, `AiProviderService`
- Jobs: `ClassifyStudentJob`, `GenerateCurriculumJob`, `GradeAssessmentJob`, `CloseAttendanceSessionJob`, `ComputeRiskScoresJob`, `SendParentNotificationJob`, `SendWeeklyReportJob`
- JS: file theo trang trong `public/js/` (`assessment.js`, `quiz.js`, `tutor-chat.js`, `activity-tracker.js`, `solver.js`), dùng jQuery, CSRF setup sẵn trong `app.js`

## Lệnh thường dùng

```bash
php artisan migrate:fresh --seed   # reset DB dev
php artisan test                   # Pest
php artisan queue:work             # chạy job local (driver theo .env, local = database)
php artisan schedule:list          # kiểm tra scheduler
```

## Tài khoản seed (dev)

admin@hoctoan.test / teacher1@hoctoan.test / student1..10@hoctoan.test / parent1..5@hoctoan.test — password chung: `password`. Student1 có sẵn curriculum + lịch sử 2 tuần để test dashboard/risk.

## Trạng thái student (state machine — không nhảy cóc)

`registered → onboarded → assessed → classified → curriculum_active → learning`
Middleware `EnsureStudentAssessed` chặn vào curriculum khi chưa assessed.
