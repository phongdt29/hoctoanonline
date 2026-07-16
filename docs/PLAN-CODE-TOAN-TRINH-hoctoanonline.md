# PLAN CODE TOÀN TRÌNH — HOCTOANONLINE.COM
## Kế hoạch triển khai từ repo trống → production
**Stack:** Laravel 11 + MySQL 8 + Blade + jQuery · VPS Ubuntu (LEMP) · Không build frontend

> **Cách dùng:** Mỗi ticket bên dưới là **1 phiên làm việc với AI agent**. Mở Claude Code tại thư mục repo, dán prompt của ticket, kèm 2 file spec (`SPEC-AI-CODING-hoctoanonline-Laravel-v3.md`, `UI-DESIGN-SPEC-hoctoanonline.md`). Làm xong ticket → chạy DoD → commit → sang ticket kế. **Không nhảy cóc**, vì ticket sau phụ thuộc ticket trước.

---

## 0. TỔNG QUAN LỘ TRÌNH

| Sprint | Tên | Ticket | Mục tiêu chốt |
|---|---|---|---|
| **S0** | Nền móng | F1–F5 | Repo chạy được, DB đầy đủ, seed, theme, layout |
| **S1** | Auth (P0 — blocker) | A1–A4 | Đăng ký/đăng nhập/reset password chạy thật |
| **S2** | Value chain lõi | C1–C6 | Assessment → Phân loại → Curriculum tự động |
| **S3** | Vòng học | L1–L5 | Lesson → Quiz → Recommendation → Dashboard |
| **S4** | AI tương tác | I1–I3 | Tutor chat, Solver text, Solver ảnh/OCR |
| **S5** | Giám sát | M1–M4 | Activity tracking → Attendance → Risk → Parent |
| **S6** | Teacher & Admin | T1–T3 | Lớp, giao/nộp bài, admin + AI provider |
| **S7** | Production | P1–P4 | Deploy, backup, monitoring, hardening |
| **S8** | Roadmap | R1–R4 | Notification đa kênh, gamification, thanh toán |

**Thời lượng tham khảo (1 dev + AI agent):** S0 2 ngày · S1 2 ngày · S2 5 ngày · S3 5 ngày · S4 4 ngày · S5 5 ngày · S6 4 ngày · S7 3 ngày → **~30 ngày làm việc đến MVP go-live** (hết S7). S8 làm sau khi có người dùng thật.

**Sơ đồ phụ thuộc:**
```
S0 → S1 → S2 → S3 → ┬→ S4
                    ├→ S5 → S6
                    └──────→ S7 (cần S1..S3 tối thiểu)
```

---

## SPRINT 0 — NỀN MÓNG (2 ngày)

### F1. Khởi tạo project + config nghiệp vụ
**Làm:**
- `composer create-project laravel/laravel hoctoan` (Laravel 11, PHP 8.3)
- `php artisan install:api` (Sanctum) · cài `predis/predis`
- Tạo `config/hoctoan.php` chứa **toàn bộ ngưỡng nghiệp vụ** (cấm hardcode về sau):
```php
return [
  'gpa_thresholds'      => ['trung_binh' => 5, 'kha' => 8],
  'quiz'                => ['duration_minutes' => 15, 'new_lesson_min' => 8, 'review_min' => 5],
  'session_mix'         => ['review' => 20, 'new' => 60, 'reinforce' => 20],
  'attendance'          => ['present_ratio' => 0.70, 'late_after_min' => 15,
                           'absent_pending_after_min' => 30, 'idle_gap_minutes' => 3],
  'risk_weights'        => ['absenteeism' => .30, 'incomplete_session' => .20,
                           'low_engagement' => .20, 'quiz_decline' => .15,
                           'missed_recommendation' => .15],
  'risk_levels'         => ['on_dinh' => 30, 'can_theo_doi' => 60],
  'solver'              => ['max_hints' => 2, 'image_per_day' => 20, 'ocr_min_confidence' => 70],
  'reset_token_ttl_min' => 30,
  'ai_timeout'          => 60,
];
```
- `.env.example` đầy đủ: `DB_*`, `REDIS_*`, `MAIL_*`, `AI_ENCRYPT_KEY`, `FILESYSTEM_DISK`
- Cài Pest: `composer require pestphp/pest --dev`

**DoD:** `php artisan serve` chạy · `php artisan test` pass · `config('hoctoan.quiz.duration_minutes')` trả 15.

---

### F2. Toàn bộ migration (§2 spec v3)
**Làm:** tạo migration theo đúng thứ tự FK:
```
users → password_reset_tokens → students → parent_accounts → parent_student_links
→ assessments → assessment_questions
→ student_classifications → classification_topic_abilities
→ curricula → curriculum_modules → lessons → exercises → quizzes → quiz_attempts
→ student_attendance_sessions → student_activity_logs
→ parent_notifications → learning_risk_scores → point_ledger
→ solver_requests → tutor_conversations → tutor_messages
→ ai_providers → ai_logs → audit_logs
→ classes → class_students → assignments → assignment_submissions
```
**Bắt buộc:** charset `utf8mb4_unicode_ci` toàn bộ · index đúng spec · `student_activity_logs` có index `[session_id, event_time]`.

**DoD:** `php artisan migrate:fresh` sạch · `migrate:rollback` không lỗi FK.

---

### F3. Models + quan hệ Eloquent
**Làm:** Model cho mọi bảng, khai báo `$fillable`, `$casts` (json → array, datetime), quan hệ:
- `User` hasOne `Student`/`ParentAccount`; `Student` belongsToMany `ParentAccount` qua `parent_student_links`
- `Student` hasMany `Assessment`, `QuizAttempt`, `AttendanceSession`; hasOne `activeCurriculum()`
- `Curriculum` hasMany `CurriculumModule` → hasMany `Lesson` → hasMany `Exercise`, hasOne `Quiz`
- **`PointLedger`:** override `update()`/`delete()` ném `RuntimeException` (append-only)

**DoD:** tinker truy vấn `Student::first()->activeCurriculum->modules` chạy được · test PointLedger update → throw.

---

### F4. Seeder dữ liệu dev
**Làm:** `DatabaseSeeder` tạo: 1 admin · 2 teachers · **10 students đủ 3 mức học lực** (3 trung_binh, 4 kha, 3 gioi) · 5 parents đã link con · 1 student có curriculum + 13 lesson completed + quiz history + attendance 2 tuần (đủ để test risk score & dashboard) · 2 ai_providers mẫu.

**DoD:** `php artisan migrate:fresh --seed` < 30s · login được bằng account seed.

---

### F5. Theme + layout Blade (theo UI Design Spec)
**Làm:**
- `public/css/theme.css`: toàn bộ token §1 + component §4 của UI spec (CSS thuần)
- `public/js/app.js`: `$.ajaxSetup` CSRF + helper `api.get/post`
- Layout: `layouts/student.blade.php` (sidebar + bottom tab), `layouts/parent.blade.php`, `layouts/admin.blade.php`, `layouts/auth.blade.php` — nạp CDN: Be Vietnam Pro + IBM Plex Mono, Bootstrap 5 (grid/utilities only), MathJax, Lucide
- Component: `<x-card>`, `<x-stat>`, `<x-status-chip>`, `<x-mastery-grid>`, `<x-quiz-option>`, `<x-chat-bubble>`, `<x-empty-state>`
- Nền lưới ô ly + `:root{--primary: {{ $student->favorite_color }} }` in từ Blade

**DoD:** trang demo dùng đủ 7 component hiển thị đúng · responsive 360px · focus ring rõ · Lighthouse a11y ≥ 90.

---

## SPRINT 1 — AUTH (P0 BLOCKER — 2 ngày)

### A1. Đăng ký / Đăng nhập / Đăng xuất
**Làm:** `RegisterController`, `LoginController` (session web + Sanctum token cho AJAX), FormRequest validate (email unique, password ≥ 8 + confirm), rate limit `throttle:5,1` cho login.
Role redirect: student→`/onboarding` hoặc `/dashboard` · parent→`/parent` · teacher→`/teacher/classes` · admin|staff→`/admin`.

**DoD:** ✅ **Fix xong bug blocker** — đăng ký → đăng nhập → vào đúng trang theo role. Feature test: đăng ký, login sai pass 5 lần → 429, logout hủy session/token.

### A2. Forgot / Reset password production
**Làm:** token random 64 ký tự → **hash trước khi lưu** · TTL 30 phút (`config('hoctoan.reset_token_ttl_min')`) · one-time (`used_at`) · gửi qua Mailable `ResetPasswordMail` (queue) · form `/reset-password/{token}`.

**DoD:** test: token hết hạn → 422 · dùng lần 2 → 422 · đổi pass xong login được bằng pass mới.

### A3. RBAC — Gate/Policy + middleware
**Làm:** middleware `role:student|parent|teacher|admin`, Policy cho từng model (student chỉ xem dữ liệu của mình; parent chỉ xem con đã link; teacher chỉ lớp mình), middleware `EnsureStudentAssessed`.

**DoD:** test ma trận quyền: student gọi `/admin/*` → 403 · parent A xem con của parent B → 403.

### A4. Audit log + trang Auth theo UI spec
**Làm:** middleware/observer ghi `audit_logs` (login, logout, register, reset) · Blade auth theo theme: nền ô ly, thẻ trắng bo 16px, nút primary tím mực.

**DoD:** login → có bản ghi audit · giao diện khớp UI spec.

---

## SPRINT 2 — VALUE CHAIN LÕI (5 ngày) ⭐ QUAN TRỌNG NHẤT

### C1. `AiProviderService` (làm TRƯỚC vì C2–C6 đều cần)
**Làm:** chọn provider `status=active` theo `priority` ASC → failover khi lỗi · `Http::timeout(config('hoctoan.ai_timeout'))` · API key `Crypt::decrypt` · safety filter in/out (nội dung hợp lứa tuổi) · **ghi `ai_logs` mọi call** (feature, latency, status) · method `chat(feature, prompt, schema)` ép JSON output + validate.

**DoD:** test với fake HTTP: provider 1 lỗi → tự dùng provider 2 · mọi call có ai_logs · AI trả JSON sai schema → retry 1 lần rồi throw.

### C2. Onboarding student
**Làm:** form Blade multi-step (3 bước: thông tin cá nhân → học tập → cá nhân hóa màu/thầy-cô/sở thích) · FormRequest validate `grade 6..12`, `math_gpa 0..10` · sinh `invite_code` · `status = onboarded`.

**DoD:** thiếu field → 422 kèm danh sách lỗi hiển thị inline · xong → redirect `/assessment`.

### C3. Assessment — tạo đề & làm bài
**Làm:** `POST /api/v1/assessments/start` → AiProviderService sinh 5–10 câu (mix trắc nghiệm/tự luận, phủ chủ đề nền tảng của `grade`, dựa `self_assessed_level` + `math_gpa`) · Blade + `public/js/assessment.js`: 1 câu/màn, **đếm `time_spent_seconds` từng câu**, autosave 30s (`PUT .../save`) · MathJax render đề.

**DoD:** refresh trang không mất bài · mỗi câu có `time_spent_seconds > 0` · đề đúng 5–10 câu.

### C4. Chấm bài + `ClassifyStudentJob` (2 tầng)
**Làm:** `POST .../submit` → `GradeAssessmentJob` chấm (trắc nghiệm rule, tự luận qua AI) → `status=graded` → dispatch `ClassifyStudentJob`:
- Tầng 1: `$base = gpa <= 5 ? trung_binh : (gpa <= 8 ? kha : gioi)` — **chỉ tham khảo**
- Tầng 2 (quyết định): AI nhận điểm test + `time_spent` từng câu + tỷ lệ sai theo topic + độ ổn định dễ/khó → trả `overall_ability`, `topic_abilities[]`, `self_learning_level`, `processing_speed`, `final_level`, `weak_topics`
- Lưu `student_classifications` + `classification_topic_abilities` → `status=classified` → dispatch `GenerateCurriculumJob`
- Queue `tries=3`, `backoff=[30,120,300]`

**DoD:** test: student gpa 8.5 nhưng làm test sai 60% → `final_level ≠ gioi` (chứng minh tầng 2 thắng tầng 1) · `topic_abilities` có đủ mọi topic xuất hiện trong đề.

### C5. `GenerateCurriculumJob` + `CurriculumService`
**Làm:** input = grade + classification + `planned_sessions` + goal → sinh 4 phase (`on_nen_tang`/`cung_co`/`nang_cao`/`luyen_de`), **phase 1 ưu tiên `weak_topics`** → modules → lessons (theory tối giản hợp học lực) → mỗi lesson: exercises 3 mức (easy/medium/hard) + 1 quiz 15' · lesson đầu `unlocked`, còn lại `locked` · `status=curriculum_active` · method `insertReviewLesson()` để S3 dùng.

**DoD:** student yếu "phan_so" → phase 1 có module phan_so · mọi lesson có ≥3 exercise đủ 3 mức + đúng 1 quiz · chỉ 1 lesson `unlocked`.

### C6. Trang kết quả + màn hình lộ trình
**Làm:** trang kết quả assessment (điểm, phân tích lỗi theo topic dạng thanh màu) · trang `/curriculum` hiển thị **Mastery Grid** (signature UI) theo 4 phase.

**DoD:** ✅ **E2E chuỗi lõi:** đăng ký → onboarding → làm test → (queue chạy) → có curriculum → thấy Mastery Grid. Đây là mốc nghiệm thu lớn nhất của dự án.

---

## SPRINT 3 — VÒNG HỌC (5 ngày)

### L1. Lesson viewer
**Làm:** `/lessons/{id}` — Policy chặn lesson `locked` · hiển thị theory (MathJax) + exercises theo 3 mức · nút "Làm bài tập" → chấm ngay, sai thì hiện gợi ý · đánh dấu `in_progress` khi mở.

**DoD:** vào lesson locked → 403 · làm hết exercise → mở nút "Vào quiz".

### L2. Quiz — timer SERVER-SIDE (chống gian lận)
**Làm:** `POST /quizzes/{id}/start` → server tạo `quiz_attempt` + trả `expires_at` · `public/js/quiz.js` chỉ **hiển thị** đếm ngược từ `expires_at`, còn <2 phút đổi `--danger` (tôn trọng `prefers-reduced-motion`), hết giờ auto submit · `POST /submit`: **server kiểm tra `now() > expires_at` → từ chối/chấm phần đã làm** · chấm + `error_analysis` theo topic + `suggestion` (`hoc_tiep`/`on_lai`) · ghi `point_ledger` (reason `quiz_score`).

**DoD:** sửa giờ máy client → server vẫn chốt đúng · submit sau hết giờ → bị chặn · điểm vào ledger đúng 1 lần (không double).

### L3. `RecommendationService` — multi-signal
**Làm:** rule nền: `>=8` bài mới · `5..<8` bài mới + 30% ôn · `<5` ôn trước.
**Tầng multi-signal (bắt buộc):** quiz_score gần nhất, `hint_count`, số lần sai-rồi-sửa-đúng, thời gian làm bài, lỗi lặp theo topic, độ quên (spaced repetition decay), độ ổn định 3–5 buổi.
Output mỗi buổi trả lời **đồng thời 3 câu**: `new_content` (60%) · `review_content` (20% phần dễ quên) · `reinforce_content` (20% lỗi gần đây) · `priority`.
Điểm <5 → gọi `insertReviewLesson()` chèn buổi ôn vào curriculum (chứng minh curriculum **động**, không lập sẵn cứng).

**DoD:** test 5 kịch bản seed: yếu topic A → **không** bị đẩy sang topic B phụ thuộc A · thay đổi tín hiệu → recommendation đổi · điểm <5 → curriculum có lesson ôn mới chèn vào.

### L4. Dashboard student
**Làm:** `GET /api/v1/dashboard/student` gộp: `completion_percent`, `sessions_done/remaining`, `avg_quiz_score`, `weak_topics`, `today_recommendation`, `points_balance`, `streak_days` · Blade đúng mockup: thanh mix **20/60/20**, Mastery Grid, chips streak/điểm, thẻ gia sư.

**DoD:** khớp mockup · 1 request duy nhất (không N+1 — dùng eager loading).

### L5. Khoảnh khắc signature
**Làm:** quiz ≥8 → ô tương ứng Mastery Grid **tô màu lan** (scale 0→1, 400ms) + chip ⭐ nảy 1 lần · unlock lesson kế · cập nhật `streak_days`.

**DoD:** animation chạy 1 lần, tắt khi `prefers-reduced-motion` · không confetti toàn màn hình.

---

## SPRINT 4 — AI TƯƠNG TÁC (4 ngày)

### I1. Tutor Chat (polling 3s)
**Làm:** `tutor_conversations`/`tutor_messages` · persona theo `tutor_gender` + trình độ từ classification (prompt inject) · `GET .../messages?after_id=` polling 3s, **dừng khi `document.hidden`** · "đang soạn…" 3 chấm nhún · bong bóng theo UI spec.

**DoD:** đổi tab → polling dừng · persona cô/thầy khác nhau rõ · mọi message có `ai_logs`.

### I2. Solver text — 3 bước chống lệ thuộc đáp án ⭐
**Làm:** `POST /solver/text` → **hint mở, KHÔNG lộ đáp án** · `POST /{id}/more-hint` → hint sâu hơn, **max 2** (`hint_count`) · `POST /{id}/full-solution` → step-by-step, `solution_revealed=true` · `GET /{id}/similar` sinh bài tương tự.
UI: stepper dọc, nút "Xem lời giải" là **ghost** không phải primary · vi copy: "Mình gợi ý trước nhé, xem full lời giải sau."
Flag `answer_dependency` khi tỷ lệ reveal-không-thử-làm cao.

**DoD:** gọi lần đầu → response **không chứa đáp án cuối** (test assert) · hint thứ 3 → 422 · reveal mới có full solution.

### I3. Solver ảnh / OCR
**Làm:** upload → Storage (local dev / S3 prod) · OCR → `ocr_confidence` · `< config('hoctoan.solver.ocr_min_confidence')` → **bắt student confirm đề đã parse** · RateLimiter `20 ảnh/ngày/student` · preview ảnh + trạng thái confidence rõ ràng.

**DoD:** ảnh thứ 21 trong ngày → 429 kèm thông báo tiếng Việt rõ · confidence thấp → hiện form confirm, không giải ngay.

---

## SPRINT 5 — GIÁM SÁT (5 ngày)

### M1. `activity-tracker.js` + endpoint batch
**Làm:** JS gom event (`lesson_open`, `section_view`, `exercise_start`, `answer_submit`, `hint_request`, `chat_message`, `tab_inactive`, `quiz_submit`) → `setInterval` 15s POST batch `/api/v1/activity/events` · `navigator.sendBeacon` khi `beforeunload` · `visibilitychange` → gửi `tab_inactive`.
Server: validate + bulk insert (KHÔNG xử lý nặng trong request) · throttle chống spam.

**DoD:** học 5 phút → có log đủ loại event · đóng tab → beacon gửi thành công · endpoint < 50ms.

### M2. `AttendanceService` — effective time + 3 trạng thái
**Làm:** `effective_study_time` = tổng gap giữa 2 event hợp lệ **≤ 3 phút** (`idle_gap_minutes`); `tab_inactive`/idle > 3 phút **không tính**.
Chốt: `present` = vào + effective ≥ 70% chuẩn buổi + có nộp quiz · `partial` = effective < 70% **hoặc** bỏ quiz · `absent` = không vào/gần như không tương tác.
Hiển thị: **"online X phút — học thực Y phút — mức tập trung Y/X"**.

**DoD:** test seed: online 90' nhưng chỉ 8' tương tác → hiện đúng "online 90 — học thực 8 — tập trung thấp" & `partial`.

### M3. `CloseAttendanceSessionJob` + Scheduler
**Làm:** `routes/console.php`:
```php
Schedule::job(new CloseAttendanceSessionJob)->everyFiveMinutes();
Schedule::job(new ComputeRiskScoresJob)->dailyAt('19:00');        // 2h sáng VN
Schedule::job(new SendWeeklyReportJob)->weeklyOn(1,'01:00');      // 8h sáng T2 VN
Schedule::command('hoctoan:reconcile-points')->dailyAt('20:00');
```
Flow vắng: T+15' chưa vào → `late` · T+30' → `absent_pending` · hết khung giờ → `absent` → dispatch `SendParentNotificationJob` + **tạo lesson bù** + gợi ý giờ học lại + update risk.
Supervisor: 2 process `queue:work redis --tries=3 --max-time=3600`. Cron VPS **1 dòng** `schedule:run`.

**DoD:** giả lập giờ → trạng thái chuyển đúng `late → absent_pending → absent` · vắng → có notification + lesson bù.

### M4. `RiskScoreService` + Parent dashboard 6 khối
**Làm:**
```php
$risk = .30*$absenteeism + .20*$incompleteSession + .20*$lowEngagement
      + .15*$quizDecline + .15*$missedRecommendation;   // rate 0..100, trọng số từ config
// 0–30 on_dinh 🟢 | 31–60 can_theo_doi 🟡 | 61–100 nguy_co_cao 🔴
```
Lưu `learning_risk_scores` + `components` (breakdown 5 rate).
Parent link con qua `invite_code` · Dashboard **6 khối**: lịch hôm nay · trạng thái tham gia · thời gian học thật · kết quả buổi · cảnh báo · gợi ý can thiệp. Khối đầu = **đèn tín hiệu risk** (chấm màu + chữ, không màu trần).
Rule cảnh báo: không vào sau 15' → notify · vào nhưng không active 10' → flag · không nộp quiz → cảnh báo · **vắng 2 buổi liên tiếp → mức cao** · **quiz giảm 3 buổi liên tiếp → đề xuất theo dõi**.

**DoD:** đủ 6 khối · 5 rule cảnh báo có test · parent A không xem được con parent B (403) · risk tính lại realtime khi chốt absent.

---

## SPRINT 6 — TEACHER & ADMIN (4 ngày)

### T1. Teacher: lớp + giao bài + gradebook
**Làm:** CRUD classes, thêm student vào lớp, tạo assignment (title/content/due_at), gradebook xem điểm cả lớp.
**DoD:** teacher chỉ thấy lớp mình (Policy) · gradebook không N+1.

### T2. Student nhận & nộp bài (GAP 45% — mắt xích thiếu)
**Làm:** `GET /api/v1/student/assignments` · `POST /student/assignments/{id}/submit` (text + file) · unique `[assignment_id, student_id]` · teacher chấm → `score`, `feedback`, `graded_at` → **`point_ledger` (assignment_graded)** → `SendParentNotificationJob`.
**DoD:** ✅ chuỗi đầy đủ: giao → nộp → chấm → điểm vào ledger → phụ huynh nhận thông báo. Nộp 2 lần → cập nhật, không tạo bản ghi trùng.

### T3. Admin + AI Provider CRUD
**Làm:** admin users/classes/reports/activity · CRUD `ai_providers` (key `Crypt::encrypt`, đổi `priority`, bật/tắt) · viewer `ai_logs` (lọc theo feature/thời gian) · trang `audit_logs`.
**DoD:** disable provider 1 → hệ thống tự dùng provider 2 (test thật) · key không lộ ra response (masked).

---

## SPRINT 7 — PRODUCTION (3 ngày) → GO-LIVE

### P1. Dựng VPS Ubuntu (LEMP)
```bash
Nginx + PHP-FPM 8.3 (php8.3-{fpm,mysql,redis,mbstring,xml,curl,gd,zip})
MySQL 8 (bind 127.0.0.1, user riêng — KHÔNG dùng root)
Redis · Supervisor (2 × queue:work) · certbot --nginx · ufw allow 22,80,443
Cron: * * * * * php /var/www/hoctoan/artisan schedule:run >> /dev/null 2>&1
```
**DoD:** truy cập HTTPS OK · `supervisorctl status` running · scheduler chạy (kiểm tra qua `schedule:list`).

### P2. Deploy script (KHÔNG build JS)
```bash
cd /var/www/hoctoan && php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart && php artisan up
```
**DoD:** chạy trên Ubuntu sạch từ đầu đến hết không lỗi · rollback được (git revert + migrate:rollback).

### P3. Backup + monitoring
**Làm:** `mysqldump` 2h sáng VN → nén → đẩy off-server (giữ 14 ngày) · **script restore drill + chạy thử thật 1 lần** · `/healthz` (check DB + Redis + queue) · alert khi queue fail > 10 job hoặc site down.
**DoD:** ✅ **restore thử thành công vào DB tạm** (không chỉ có script) · healthz trả 200.

### P4. Hardening trước go-live
**Làm:** `APP_DEBUG=false` · rà secret không lọt vào git · rate limit toàn API · CSP header · `storage`/`bootstrap/cache` đúng quyền · trang lỗi 403/404/500 theo theme · kiểm tra lại **toàn bộ 5 mục ưu tiên gốc**: (1) auth chạy thật (2) reset password (3) chuỗi assessment→classify→curriculum (4) student nộp bài (5) backup/monitoring.
**DoD:** ✅ **GO-LIVE.**

---

## SPRINT 8 — ROADMAP (sau khi có user thật)

| Ticket | Nội dung |
|---|---|
| **R1** | Notification đa kênh: interface `NotificationChannel` + adapter email → SMS → push; weekly report parent (cron T2, lưu history + gửi mail) |
| **R2** | Gamification mở rộng: badge, streak nâng cao, leaderboard (point_ledger đã sẵn) |
| **R3** | Thanh toán: interface `PaymentGateway` (createPayment/verifyCallback/refund) + **VNPAY, MOMO** + webhook verify signature |
| **R4** | Analytics/cohort · mobile app · partition `student_activity_logs` theo tháng khi dữ liệu lớn |

---

## PROMPT MẪU CHO AI AGENT (dùng cho mọi ticket)

```
Context: đọc kỹ 2 file trong repo: SPEC-AI-CODING-hoctoanonline-Laravel-v3.md và
UI-DESIGN-SPEC-hoctoanonline.md. Đây là nguồn sự thật duy nhất.

Nhiệm vụ: thực hiện ticket [MÃ TICKET] — [TÊN].

Ràng buộc bắt buộc:
- Laravel 11 + MySQL + Blade + jQuery. KHÔNG dùng Vite/webpack/npm build.
- Mọi ngưỡng nghiệp vụ đọc từ config/hoctoan.php, không hardcode.
- Mọi endpoint: FormRequest validation → Policy (RBAC) → Service → audit log.
- Mọi call AI đi qua AiProviderService và phải ghi ai_logs.
- Giao diện dùng token/component trong UI spec (theme.css), không tự chế màu mới.
- Viết Pest test cho business rule của ticket này.

Xong thì: chạy php artisan test, liệt kê file đã tạo/sửa, và tự đối chiếu
checklist DoD của ticket rồi báo mục nào chưa đạt.
```

---

## CHECKLIST NGHIỆM THU CUỐI (Definition of Done toàn dự án)

- [ ] `php artisan migrate:fresh --seed` sạch trên MySQL 8, utf8mb4
- [ ] **Auth end-to-end chạy thật** (bug blocker đã fix) + reset password production
- [ ] **Chuỗi tự động** assessment → classify (2 tầng) → curriculum, có retry
- [ ] Recommendation **multi-signal**, curriculum điều chỉnh động (không lập sẵn cứng)
- [ ] Solver **không lộ đáp án ở lần gọi đầu**; hint max 2
- [ ] Quiz timer & anti-cheat **quyết định ở server**
- [ ] Attendance 3 trạng thái + effective study time đúng công thức
- [ ] Risk score đúng trọng số + parent dashboard đủ 6 khối
- [ ] Student nộp teacher assignment → chấm → ledger → notify parent
- [ ] `point_ledger` không có code path update/delete
- [ ] Mọi call AI có `ai_logs`; API key mã hóa; không secret trong git
- [ ] Test: Pest cover rule §3 + e2e happy path pass
- [ ] Deploy script + backup **đã restore thử thành công** + monitoring
- [ ] UI: responsive 360px, focus ring, contrast ≥4.5:1, reduced-motion
