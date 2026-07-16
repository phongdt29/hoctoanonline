# IMPLEMENTATION SPEC — HOCTOANONLINE.COM
## v3.0 — PHP LARAVEL + MySQL + jQuery (Tài liệu cho AI Coding Agent)

> **Cách dùng:** Đưa file này cho AI coding agent làm nguồn sự thật duy nhất.
> Stack chốt: **Laravel 11 + MySQL 8 + Blade + jQuery (JS thuần, không build frontend)**.
> Deploy: VPS Ubuntu (Nginx + PHP-FPM + MySQL + Redis + Supervisor).

---

## 0. TECH STACK & CONVENTIONS (BẮT BUỘC)

```yaml
framework: Laravel 11 (PHP 8.3)
database: MySQL 8 — charset utf8mb4, collation utf8mb4_unicode_ci
orm: Eloquent + Laravel Migration/Seeder (KHÔNG viết SQL tay trên production)
frontend: Blade template + jQuery 3.x + Bootstrap 5 (CDN — KHÔNG dùng Vite/webpack)
auth_web: session-based (Laravel Breeze pattern, tự viết controller)
auth_api: Laravel Sanctum (token) — cho AJAX + mobile sau này
rbac: Gate/Policy theo cột users.role
roles: [student, parent, teacher, staff, admin]
queue: Redis + Laravel Queue (php artisan queue:work qua Supervisor)
scheduler: Laravel Task Scheduling (cron duy nhất: schedule:run mỗi phút)
realtime_chat: AJAX polling 3 giây (nâng cấp Laravel Reverb sau nếu cần)
timezone: app UTC, hiển thị Asia/Ho_Chi_Minh
locale: vi (UI tiếng Việt, code/identifier tiếng Anh)
ai_layer: gọi qua AiProviderService (proxy + failover) — KHÔNG hardcode provider
storage: local disk (dev) / S3-compatible (production) qua Laravel Filesystem
```

**Conventions:**
- Bảng `snake_case` số nhiều; Model `PascalCase` số ít; FK `<entity>_id`.
- Route web: Blade pages. Route AJAX: prefix `/api/v1/...` trả JSON, bảo vệ bằng `auth:sanctum` hoặc session + CSRF.
- Mọi controller action: FormRequest validation → Policy check → Service → JSON/view.
- Config nghiệp vụ (ngưỡng 70%, 15 phút, trọng số risk...) đặt trong `config/hoctoan.php` — KHÔNG hardcode.
- Điểm thưởng chỉ ghi qua `PointLedgerService` (append-only).
- Mọi request AI ghi vào bảng `ai_logs` qua middleware/service.

**Cấu trúc thư mục chính agent phải theo:**
```
app/
  Http/Controllers/{Auth,Student,Parent,Teacher,Admin,Api}/
  Http/Requests/            # FormRequest validation
  Models/
  Services/                 # ClassificationService, CurriculumService,
                            # RecommendationService, SolverService,
                            # AttendanceService, RiskScoreService,
                            # PointLedgerService, AiProviderService
  Jobs/                     # ClassifyStudentJob, GenerateCurriculumJob,
                            # SendParentNotificationJob, ComputeRiskScoresJob,
                            # CloseAttendanceSessionJob, SendWeeklyReportJob
  Policies/
database/migrations/  database/seeders/
resources/views/{student,parent,teacher,admin,auth}/   # Blade
public/js/            # jQuery modules thuần: app.js, quiz.js, tutor-chat.js,
                      # activity-tracker.js, solver.js
routes/web.php  routes/api.php  routes/console.php (scheduler)
config/hoctoan.php
```

---

## 1. USER FLOW LÕI & STATE MACHINE

```
[Đăng ký] → [Onboarding] → [Assessment đầu vào] → [AI phân loại]
→ [AI generate curriculum] → [Dashboard] → [Học lesson] → [Quiz 15']
→ [AI recommendation buổi sau] → lặp lại
```

`students.status`: `registered → onboarded → assessed → classified → curriculum_active → learning`

- Middleware `EnsureStudentAssessed`: chưa `assessed` → redirect về trang assessment.
- Submit assessment → dispatch `ClassifyStudentJob` → khi xong dispatch `GenerateCurriculumJob` (queue Redis, `tries=3`, `backoff=[30,120,300]`).

---

## 2. MIGRATIONS (SCHEMA)

> Agent tạo migration Laravel theo đúng cấu trúc dưới. Kiểu cột ghi theo schema builder.

### 2.1. users + auth
```php
// users
id; string email unique; string password; enum role [student,parent,teacher,staff,admin];
enum status [active,disabled] default active; rememberToken; timestamps;

// password_reset_tokens (dùng bảng chuẩn Laravel — TTL config 30 phút, one-time)
// personal_access_tokens (Sanctum — php artisan install:api)
```

### 2.2. students
```php
id; foreignId user_id unique constrained cascadeOnDelete;
string full_name(150); date date_of_birth; string address; string phone(20);
string school_name(150); unsignedTinyInteger grade;            // 6..12 (validate FormRequest)
enum self_assessed_level [trung_binh,kha,gioi];
decimal math_gpa(4,2);                                          // 0..10 — CHỈ tham khảo
enum tutor_gender [thay,co] default thay;
string favorite_color(30) nullable; json interests nullable;
enum status [registered,onboarded,assessed,classified,curriculum_active,learning] default registered;
integer points_balance default 0; integer streak_days default 0;
string invite_code(10) unique;                                  // parent link
timestamps;
```

### 2.3. assessments + assessment_questions
```php
// assessments
id; foreignId student_id constrained; enum status [in_progress,submitted,graded] default in_progress;
decimal score(4,2) nullable; timestamp started_at; timestamp submitted_at nullable; timestamps;

// assessment_questions
id; foreignId assessment_id constrained cascadeOnDelete; unsignedTinyInteger question_order;
enum type [multiple_choice,essay]; string topic(80) index;
enum difficulty [easy,medium,hard]; text content;
json options nullable; json correct_answer; json student_answer nullable;
boolean is_correct nullable; integer time_spent_seconds default 0;   // BẮT BUỘC track
```

### 2.4. classification
```php
// student_classifications
id; foreignId student_id constrained; foreignId assessment_id constrained;
unsignedTinyInteger overall_ability;        // 0..100
unsignedTinyInteger self_learning_level; unsignedTinyInteger processing_speed;
enum base_level [trung_binh,kha,gioi];      // tầng 1 — tham khảo
enum final_level [trung_binh,kha,gioi];     // sau AI hiệu chỉnh — QUYẾT ĐỊNH
json weak_topics; timestamps;

// classification_topic_abilities  (năng lực THEO TỪNG CHUYÊN ĐỀ — bắt buộc)
id; foreignId classification_id constrained cascadeOnDelete;
string topic(80); unsignedTinyInteger ability; decimal error_rate(5,2);
unique [classification_id, topic];
```

### 2.5. curriculum → module → lesson → exercise → quiz
```php
// curricula
id; foreignId student_id constrained; foreignId classification_id constrained;
enum status [active,archived] default active; string goal nullable;
integer planned_sessions; timestamps;

// curriculum_modules
id; foreignId curriculum_id constrained cascadeOnDelete;
unsignedTinyInteger phase;    // 1 ôn nền tảng | 2 củng cố | 3 nâng cao | 4 luyện đề
string topic(80); integer module_order;

// lessons
id; foreignId module_id constrained cascadeOnDelete; integer lesson_order;
string title(200); mediumText theory_content;
enum status [locked,unlocked,in_progress,completed] default locked;

// exercises
id; foreignId lesson_id constrained cascadeOnDelete;
enum difficulty [easy,medium,hard]; text content; json answer;

// quizzes
id; foreignId lesson_id unique constrained cascadeOnDelete;
unsignedTinyInteger duration_minutes default 15;

// quiz_attempts
id; foreignId quiz_id constrained; foreignId student_id constrained;
decimal score(4,2) nullable; json error_analysis nullable;
enum suggestion [hoc_tiep,on_lai] nullable;
timestamp started_at; timestamp submitted_at nullable;
index [student_id, submitted_at];
```

### 2.6. attendance + activity
```php
// student_attendance_sessions
id; foreignId student_id constrained; foreignId lesson_id constrained;
dateTime scheduled_start_time; dateTime actual_start_time nullable; dateTime actual_end_time nullable;
enum attendance_status [present,partial,absent,late,absent_pending] default absent_pending;
integer effective_study_minutes default 0; integer idle_minutes default 0;
decimal completion_rate(5,2) default 0;
index [student_id, scheduled_start_time];

// student_activity_logs   (ghi rất nhiều — mọi query PHẢI kèm điều kiện thời gian)
id; foreignId session_id constrained cascadeOnDelete;
enum event_type [lesson_open,section_view,exercise_start,answer_submit,
                 hint_request,chat_message,tab_inactive,quiz_submit];
dateTime event_time(3); json metadata nullable;
index [session_id, event_time];
```

### 2.7. parent
```php
// parent_accounts
id; foreignId user_id unique constrained cascadeOnDelete;
string full_name(150); string phone(20);
enum relation_to_student [bo,me,nguoi_giam_ho]; timestamps;

// parent_student_links   (1 phụ huynh nhiều con)
id; foreignId parent_id constrained cascadeOnDelete;
foreignId student_id constrained cascadeOnDelete;
enum linked_via [invite_code,admin]; timestamps;
unique [parent_id, student_id];

// parent_notifications
id; foreignId parent_id constrained; foreignId student_id constrained;
string notification_type(50);   // session_start|session_done|absent|alert_high|weekly_report...
string title(200); text content;
enum channel [in_app,email,sms,push] default in_app;
timestamp sent_at; timestamp read_at nullable;
index [parent_id, sent_at];
```

### 2.8. risk, points, solver, AI, audit
```php
// learning_risk_scores
id; foreignId student_id constrained; unsignedTinyInteger risk_score;
enum level [on_dinh,can_theo_doi,nguy_co_cao]; json components;   // breakdown 5 rate
timestamp computed_at; index [student_id, computed_at];

// point_ledger  (APPEND-ONLY: Model không có update/delete; cấm mass assignment sửa)
id; foreignId student_id constrained; integer amount;   // dương/âm
enum reason [assessment_complete,quiz_score,assignment_graded,admin_adjustment];
unsignedBigInteger ref_id nullable; timestamp created_at; index student_id;

// solver_requests
id; foreignId student_id constrained; enum input_type [text,image];
text problem_text nullable; string image_url(500) nullable;
decimal ocr_confidence(5,2) nullable;
unsignedTinyInteger hint_count default 0;        // max 2
boolean solution_revealed default false; timestamps;
index [student_id, created_at];                  // rate-limit 20 ảnh/ngày

// tutor_conversations + tutor_messages
id; foreignId student_id constrained; string title nullable; timestamps;
id; foreignId conversation_id constrained cascadeOnDelete;
enum sender [student,ai]; text content; timestamps;

// ai_providers
id; string name(100); string base_url; text api_key_encrypted;   // Crypt::encrypt
json models; enum status [active,disabled] default active;
unsignedTinyInteger priority default 1;          // failover theo priority

// ai_logs
id; foreignId provider_id nullable; foreignId student_id nullable;
string feature(50);   // assessment_gen|grading|curriculum|tutor_chat|solver|recommendation
json request_json; json response_json nullable; integer latency_ms nullable;
enum status [ok,error,filtered]; timestamp created_at;
index [feature, created_at];

// audit_logs
id; foreignId user_id nullable; string action(100);
string entity(60) nullable; unsignedBigInteger entity_id nullable;
json metadata nullable; timestamp created_at; index [user_id, created_at];
```

### 2.9. teacher
```php
// classes: id; foreignId teacher_id (users); string name(150); tinyInteger grade;
// class_students: pivot [class_id, student_id] primary
// assignments: id; foreignId class_id cascadeOnDelete; string title(200); text content; dateTime due_at;
// assignment_submissions:   ← GAP 45%: luồng student nộp bài
id; foreignId assignment_id constrained cascadeOnDelete; foreignId student_id constrained;
text content nullable; string file_url(500) nullable;
timestamp submitted_at; decimal score(4,2) nullable;
timestamp graded_at nullable; text feedback nullable;
unique [assignment_id, student_id];
```

---

## 3. BUSINESS RULES → SERVICE LAYER

### 3.1. `ClassificationService` — 2 tầng
```php
// Tầng 1 (CHỈ tham khảo):
$base = $gpa <= 5 ? 'trung_binh' : ($gpa <= 8 ? 'kha' : 'gioi');

// Tầng 2 (QUYẾT ĐỊNH): gọi AI với payload:
//  - điểm assessment, time_spent_seconds từng câu
//  - tỷ lệ sai theo topic, độ ổn định câu dễ/khó, gpa (tham khảo)
// Output BẮT BUỘC: overall_ability, topic_abilities[], self_learning_level,
//                  processing_speed, final_level, weak_topics
// → lưu student_classifications + classification_topic_abilities
// → students.status = classified → dispatch GenerateCurriculumJob
```

### 3.2. `CurriculumService`
- Input: grade + classification (vector năng lực) + planned_sessions + goal.
- Sinh 4 phase; **phase 1 ưu tiên `weak_topics`**; mỗi lesson có exercises 3 mức + 1 quiz 15'.
- Lesson đầu `unlocked`, còn lại `locked`; mở tuần tự khi lesson trước `completed`.
- Cho phép điều chỉnh động: `insertReviewLesson()` khi recommendation yêu cầu ôn.

### 3.3. `RecommendationService` — multi-signal, KHÔNG tuyến tính
```php
// Rule nền: quiz >= 8 → bài mới | 5..<8 → bài mới + 30% ôn | < 5 → ôn TRƯỚC
// Multi-signal (bắt buộc): quiz_score gần nhất, hint_count, sai-rồi-sửa-đúng,
//   thời gian làm bài, lỗi lặp theo topic, độ quên (spaced repetition),
//   độ ổn định 3–5 buổi gần nhất.
// Output mỗi buổi — trả lời ĐỒNG THỜI 3 câu:
return [
  'new_content'       => [...],   // ~60%
  'review_content'    => [...],   // ~20% phần dễ quên
  'reinforce_content' => [...],   // ~20% lỗi gần đây
  'priority'          => 'new_first' | 'review_first',
];
```

### 3.4. `SolverService` — chống lệ thuộc đáp án
```
Bước 1: trả HINT MỞ — không lộ đáp án
Bước 2: "cần thêm gợi ý" → hint sâu hơn (hint_count max 2)
Bước 3: "xem lời giải" → full step-by-step, set solution_revealed=true
Flag answer_dependency nếu tỷ lệ reveal-không-thử-làm cao.
Image: upload → Storage (S3 prod) → OCR → confidence < config('hoctoan.ocr_min_confidence')
       → bắt student confirm đề. RateLimiter: 20 ảnh/ngày/student.
```

### 3.5. `AttendanceService`
```php
// effective_study_time: gap giữa 2 event hợp lệ <= 3 phút mới tính active;
// tab_inactive / idle > 3 phút KHÔNG tính.
// Chốt trạng thái:
present : có vào + effective >= 70% chuẩn buổi + có nộp quiz
partial : effective < 70% HOẶC bỏ quiz
absent  : không vào / gần như không tương tác
// Flow (scheduler xử lý):
T+15' chưa vào → late;  T+30' → absent_pending;  hết khung giờ → chốt absent
  → SendParentNotificationJob + tạo lesson bù + gợi ý giờ học lại + update risk
// Hiển thị: "online X phút — học thực Y phút — mức tập trung Y/X"
```

### 3.6. `RiskScoreService`
```php
$risk = 0.30*$absenteeism + 0.20*$incompleteSession + 0.20*$lowEngagement
      + 0.15*$quizDecline + 0.15*$missedRecommendation;   // các rate 0..100
// 0–30 on_dinh 🟢 | 31–60 can_theo_doi 🟡 | 61–100 nguy_co_cao 🔴
// Trọng số đọc từ config/hoctoan.php
```

### 3.7. Cảnh báo phụ huynh
| Trigger | Action |
|---|---|
| Không vào học sau 15' từ giờ học | Notify parent |
| Vào nhưng không active 10' | Flag nguy cơ bỏ buổi |
| Không nộp quiz cuối buổi | Cảnh báo chưa hoàn thành |
| Vắng 2 buổi liên tiếp | Cảnh báo mức cao |
| Quiz giảm 3 buổi liên tiếp | Đề xuất theo dõi |

### 3.8. `AiProviderService`
- Chọn provider `status=active` theo `priority` tăng dần; lỗi → failover provider kế.
- Timeout `Http::timeout(config('hoctoan.ai_timeout'))`; mọi call AI dài (tạo đề, curriculum) chạy trong Job, KHÔNG trong request.
- Safety filter input/output (nội dung hợp lứa tuổi) trước khi trả; log tất cả vào `ai_logs`.

---

## 4. SCHEDULER & JOBS

`routes/console.php`:
```php
Schedule::job(new CloseAttendanceSessionJob)->everyFiveMinutes();   // late/absent_pending/absent
Schedule::job(new ComputeRiskScoresJob)->dailyAt('19:00');          // 19:00 UTC = 2:00 sáng VN
Schedule::job(new SendWeeklyReportJob)->weeklyOn(1, '01:00');       // 8:00 sáng thứ 2 VN
Schedule::command('hoctoan:reconcile-points')->dailyAt('20:00');    // đối soát points_balance
```
Cron trên VPS chỉ 1 dòng: `* * * * * php /var/www/hoctoan/artisan schedule:run >> /dev/null 2>&1`

Jobs (queue Redis, tries=3): `ClassifyStudentJob`, `GenerateCurriculumJob`, `SendParentNotificationJob`, `GradeAssessmentJob`.

---

## 5. ROUTES & CONTROLLERS

### Web (Blade, session auth)
```php
// auth: /register /login /logout /forgot-password /reset-password/{token}
// student: /onboarding /assessment /dashboard /lessons/{id} /quiz/{id} /solver /tutor /profile
// parent:  /parent (dashboard 6 khối) /parent/link-student
// teacher: /teacher/classes /teacher/assignments /teacher/gradebook
// admin:   /admin/users /admin/ai-providers /admin/reports /admin/settings
```

### API AJAX (`routes/api.php`, prefix /api/v1, auth:sanctum + throttle)
```php
POST assessments/start | PUT assessments/{id}/save | POST assessments/{id}/submit
GET  assessments/{id}/result
GET  classifications/me
GET  curricula/active | lessons/{id}
POST quizzes/{id}/start | quizzes/{id}/submit          // timer SERVER-SIDE
GET  recommendations/today | dashboard/student
POST solver/text | solver/image                        // throttle:solver-image (20/ngày)
POST solver/{id}/more-hint | solver/{id}/full-solution
GET  solver/history | solver/{id}/similar
POST tutor/conversations | tutor/conversations/{id}/messages
GET  tutor/conversations/{id}/messages?after_id=...    // jQuery polling 3s
POST activity/events                                   // batch mỗi 15s
GET  parent/dashboard | POST parent/link-student
GET  student/assignments | POST student/assignments/{id}/submit
CRUD admin/ai-providers  | POST ai/proxy/chat
```

---

## 6. FRONTEND — BLADE + jQUERY (KHÔNG BUILD)

- Load qua CDN trong layout: jQuery 3.x, Bootstrap 5, MathJax (render công thức toán).
- CSRF cho mọi AJAX:
```js
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
```
- `public/js/activity-tracker.js`: gom event (scroll/click/answer/tab visibilitychange) vào mảng, `setInterval` 15s POST batch `/api/v1/activity/events`; dùng `navigator.sendBeacon` khi `beforeunload`.
- `public/js/quiz.js`: đếm ngược hiển thị từ `expires_at` server trả về; hết giờ tự submit; **server mới là nơi quyết định hết giờ** — client chỉ hiển thị.
- `public/js/tutor-chat.js`: polling `GET .../messages?after_id={lastId}` mỗi 3s; dừng polling khi tab ẩn (`document.hidden`).
- `public/js/solver.js`: 3 nút tuần tự — Gợi ý → Gợi ý thêm (ẩn sau 2 lần) → Xem lời giải.
- Theme cá nhân hóa: Blade in CSS variable từ `favorite_color`:
```html
<style>:root { --primary: {{ $student->favorite_color ?? '#0d6efd' }}; }</style>
```

---

## 7. DEPLOY VPS UBUNTU (LEMP)

```bash
# Thành phần (VPS khuyến nghị 2 vCPU / 4GB RAM):
- Nginx + PHP-FPM 8.3 (php8.3-fpm, php8.3-mysql, -redis, -mbstring, -xml, -curl, -gd, -zip)
- MySQL 8 (bind 127.0.0.1, user riêng cho app)
- Redis (queue + cache)
- Supervisor: giữ 2 process "php artisan queue:work redis --tries=3 --max-time=3600"
- Cron: 1 dòng schedule:run (xem §4)
- SSL: certbot --nginx
- ufw: chỉ mở 22, 80, 443

# Deploy script (mỗi lần release — KHÔNG có bước build JS):
cd /var/www/hoctoan
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up

# Backup: mysqldump hằng ngày 2h sáng VN → nén → đẩy off-server; có script restore drill.
```

`.env` bắt buộc có: `DB_*`, `REDIS_*`, `MAIL_*` (reset password + notify parent), `AI_ENCRYPT_KEY`, `FILESYSTEM_DISK=s3` (prod), các ngưỡng nghiệp vụ map vào `config/hoctoan.php`.

---

## 8. THỨ TỰ IMPLEMENT (P0 → P4)

| Ưu tiên | Việc |
|---|---|
| P0 | Auth end-to-end: register/login/logout + role redirect (fix blocker hiện tại) |
| P0 | Forgot/reset password production (token TTL 30', one-time, gửi mail) |
| P0 | Toàn bộ migration §2 + seeder (1 admin, 2 teachers, 10 students đủ 3 mức, 5 parents đã link) |
| P1 | Chuỗi tự động: Assessment → ClassifyStudentJob → GenerateCurriculumJob |
| P1 | RecommendationService multi-signal (§3.3) + dashboard student |
| P2 | Student nộp teacher assignment (§2.9) → chấm → gradebook → point_ledger → notify parent |
| P2 | Solver image/OCR production (S3, confidence, rate-limit 20/ngày) |
| P2 | Attendance + activity-tracker.js + RiskScoreService + scheduler (§3.5–3.6, §4) |
| P3 | Notification đa kênh (adapter in_app/email trước) + weekly report parent |
| P3 | Admin AI provider CRUD + failover + backup/monitoring |
| P4 | Gamification mở rộng (badge/streak/leaderboard), analytics, VNPAY/MOMO, mobile |

---

## 9. DEFINITION OF DONE (MỌI MODULE)

- [ ] Migration + seeder chạy sạch trên MySQL 8 (`php artisan migrate:fresh --seed`).
- [ ] FormRequest validation + Policy (RBAC) + audit log cho mọi endpoint; student gọi route admin → 403.
- [ ] Ngưỡng nghiệp vụ đọc từ `config/hoctoan.php`, không hardcode.
- [ ] Feature test (Pest/PHPUnit) cho rule §3 + e2e happy path §1 pass.
- [ ] Quiz timer và mọi anti-cheat quyết định ở SERVER.
- [ ] Mọi call AI có bản ghi `ai_logs`; API key mã hóa bằng `Crypt`, không secret trong code.
- [ ] `point_ledger` không tồn tại code path update/delete.
- [ ] `.env.example` đầy đủ biến; deploy script §7 chạy được trên Ubuntu sạch.
