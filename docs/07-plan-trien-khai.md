# 07 — Plan triển khai

## Bối cảnh

- **Stack đã chốt:** PHP + MySQL trên XAMPP (Laravel)
- **Điểm xuất phát:** greenfield — repo chưa có code
- **Nguồn yêu cầu:** [02-dac-ta-module.md](02-dac-ta-module.md) (11 module) + [03-logic-ai.md](03-logic-ai.md) (logic A.I)

> ⚠️ Sheet `Task` (xem [05-tien-do.md](05-tien-do.md)) mô tả tiến độ của một codebase khác đã bị loại khỏi phạm vi. **Không dùng các con số tiến độ đó nữa** — dự án này bắt đầu từ 0.

## Môi trường đã kiểm tra

| Thành phần | Trạng thái |
|---|---|
| PHP | 8.2.12 (ZTS) ✅ |
| Composer | 2.9.7 ✅ |
| MariaDB | 10.4.32 ✅ (client OK, **service chưa chạy**) |
| Apache | Đang chạy port 80 ✅ |
| Extension `gd` | ❌ **Thiếu** — cần cho Solver ảnh |
| Extension khác | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, curl, zip ✅ |

### Hai lưu ý kỹ thuật

**1. MariaDB 10.4, không phải MySQL.** Kiểu `JSON` là bí danh của `LONGTEXT` — không có validate/index native. Ảnh hưởng `student_activity_logs.metadata`. Chấp nhận được, nhưng đừng kỳ vọng truy vấn JSON hiệu năng cao; nếu cần lọc theo field bên trong metadata thì tách ra cột riêng.

**2. `gd` thiếu.** Bật trong `c:\xampp\php\php.ini` (bỏ `;` trước `extension=gd`) rồi restart Apache. Cần cho Giai đoạn 6.

---

## Nguyên tắc thứ tự

Thứ tự dưới đây theo **phụ thuộc dữ liệu**, không theo độ khó. Mỗi giai đoạn chỉ bắt đầu khi giai đoạn trước có dữ liệu thật để nó ăn vào.

```
G0 Nền móng ─→ G1 Hồ sơ ─→ G2 Assessment ─→ G3 Giáo trình ─→ G4 Học+Quiz ─→ G5 Recommendation
                                                                    │
                                                                    ├─→ G6 AI Tutor/Solver
                                                                    └─→ G7 Tracking ─→ G8 Parent
                                                                                        │
                                                                                   G9 Thanh toán
```

**G2 → G3 → G5 là đường găng.** Đây là chuỗi giá trị cốt lõi; mọi thứ khác là vệ tinh.

---

## Giai đoạn 0 — Nền móng

**Mục tiêu:** chạy được `hello world` có đăng nhập, đúng 4 vai trò.

| # | Việc | Ghi chú |
|---|---|---|
| 0.1 | Bật `gd` trong php.ini, khởi động MySQL trong XAMPP Control Panel | Chặn G6 nếu bỏ qua |
| 0.2 | `composer create-project laravel/laravel` (bản mới nhất hợp PHP 8.2) | Xác nhận version lúc cài, đừng đoán |
| 0.3 | Cấu hình `.env` → MariaDB, tạo database `hoctoanonline` | |
| 0.4 | Chạy qua `php artisan serve`, **không** qua Apache | Xem mục "Apache hay artisan serve" bên dưới |
| 0.5 | Migration `users` + RBAC 4 vai trò | student / parent / teacher / admin |
| 0.6 | Đăng nhập, đăng xuất, role redirect | |
| 0.7 | Layout cơ bản + Tailwind | |
| 0.8 | Cập nhật lại docs 01/04/05 cho khớp stack PHP+MySQL | Hiện đang ghi Mongoose — đã sai |

**Xong khi:** 4 vai trò đăng nhập được, mỗi vai trò về đúng trang của mình.

> ❓ **Cần chốt:** đặc tả gốc (sheet `CÁC MODULE`) **không có module giáo viên và admin**. Hai vai trò này chỉ xuất hiện trong sheet `Task` — tức thuộc về codebase đã loại bỏ. Nếu bám đúng đặc tả thì chỉ cần **student + parent**. Xem Q11 trong [06-van-de-can-chot.md](06-van-de-can-chot.md).

---

## Giai đoạn 1 — Hồ sơ & Đăng ký

**Nguồn:** Module 1 + Module 8

| # | Việc |
|---|---|
| 1.1 | Migration `student_profiles` — đủ 12 trường bắt buộc |
| 1.2 | Form đăng ký nhiều bước + validate |
| 1.3 | Onboarding sau submit |
| 1.4 | Trang profile (xem/sửa) |
| 1.5 | Theme màu theo `mau_sac_yeu_thich` |
| 1.6 | Chọn tutor thầy/cô → lưu preference |

**Xong khi:** đăng ký thật tạo được hồ sơ đủ 12 trường, UI đổi màu theo lựa chọn.

---

## Giai đoạn 2 — Assessment & Phân loại 🔴 ĐƯỜNG GĂNG

**Nguồn:** Module 2 + Logic mục 1, 2

> 🔴 **Chặn:** phải chốt **Q1** ([06-van-de-can-chot.md](06-van-de-can-chot.md)) trước khi viết dòng code nào ở đây. Q1 quyết định schema của `skill_profiles` và mọi thứ phía sau.

| # | Việc | Ghi chú |
|---|---|---|
| 2.1 | Tích hợp AI provider (API key, wrapper, retry, log) | Chọn provider trước |
| 2.2 | Sinh đề 5–10 câu từ lớp + học lực + điểm TB + trường | |
| 2.3 | Luồng làm bài, lưu từng câu | |
| 2.4 | **Đề thích nghi** — dùng `kết quả các câu trước` | Đặc tả có nêu, dễ bị bỏ sót |
| 2.5 | Chấm điểm + phân tích lỗi theo chủ đề | |
| 2.6 | Ghi nhận thời gian làm **từng câu** | Input bắt buộc của 2.7 |
| 2.7 | **Phân loại 2 tầng** — rule cứng → AI hiệu chỉnh | Logic 1.3 |
| 2.8 | Sinh `skill_profiles`: năng lực tổng quát + **theo chuyên đề** + mức tự học + tốc độ | Không phải 3 nhãn |

**Xong khi:** học sinh làm test xong → hệ thống có profile năng lực theo chuyên đề, **tự động**, không thao tác tay.

---

## Giai đoạn 3 — Giáo trình (CORE ENGINE) 🔴 ĐƯỜNG GĂNG

**Nguồn:** Module 3 + Logic mục 3

| # | Việc |
|---|---|
| 3.1 | Migration `curricula`, `curriculum_modules`, `lessons`, `exercises`, `quizzes` |
| 3.2 | Sinh giáo trình từ `skill_profiles` (**không** từ nhãn 3 mức) |
| 3.3 | Cấu trúc 4 giai đoạn: ôn nền → củng cố → nâng cao → luyện đề |
| 3.4 | Lý thuyết chia theo chuyên đề |
| 3.5 | Bài tập 3 cấp: Dễ → TB → Khó |
| 3.6 | Quiz 15 phút gắn mỗi lesson |

**Xong khi:** mỗi học sinh có giáo trình riêng, nối thẳng từ kết quả G2, khác nhau rõ rệt giữa 2 học sinh khác profile.

---

## Giai đoạn 4 — Học & Quiz

**Nguồn:** Module 4 + Module 5

| # | Việc |
|---|---|
| 4.1 | Dashboard: % hoàn thành, buổi đã học, buổi còn lại, điểm TB, nhóm kiến thức yếu |
| 4.2 | Lesson viewer (lý thuyết + bài tập) |
| 4.3 | Quiz 15 phút — **timer phía server** |
| 4.4 | Chấm tự động + phân tích lỗi sai |
| 4.5 | `lesson_progress`, `quiz_attempts` |

> ⚠️ Timer phải ở server. Timer phía client thì học sinh sửa được bằng DevTools.

---

## Giai đoạn 5 — Recommendation 🔴 ĐƯỜNG GĂNG

**Nguồn:** Module 6 + Logic mục 4

> ❓ Chốt **Q4** trước: chọn-một-trong-ba (4.2) hay trộn 20/60/20 (4.5).

| # | Việc |
|---|---|
| 5.1 | Thu thập 7 tín hiệu (Logic 4.4): điểm quiz, số lần xin gợi ý, sai-rồi-sửa-đúng, thời gian làm, lỗi lặp lại, mức quên, độ ổn định 3–5 buổi |
| 5.2 | Engine trả lời **3 câu**: học gì mới / ôn lại gì / ưu tiên mức nào |
| 5.3 | Cấu trúc buổi 20/60/20 |
| 5.4 | Điều chỉnh tỷ lệ theo điểm quiz |
| 5.5 | "Gợi ý học hôm nay" trên dashboard |

**Xong khi:** hai học sinh cùng điểm quiz nhưng khác hành vi → nhận gợi ý khác nhau. Nếu giống nhau là vẫn đang "giả cá nhân hóa".

---

## Giai đoạn 6 — AI Tutor & Solver

**Nguồn:** Module 3 (AI Tutor) + Module 7 + Logic mục 5

| # | Việc | Ghi chú |
|---|---|---|
| 6.1 | Chat AI Tutor, phong cách thầy/cô | |
| 6.2 | Solver nhập text → giải step-by-step | |
| 6.3 | Solver nhập **ảnh** → OCR | **Cần `gd`** + chọn OCR engine |
| 6.4 | **Gợi mở trước, không bung đáp án ngay** | Logic mục 5 — ràng buộc bắt buộc |
| 6.5 | Đếm `hint_request`, phát hiện xin-đáp-án | ❓ Q9: ngưỡng bao nhiêu |
| 6.6 | Gợi ý bài tương tự | |
| 6.7 | Lịch sử hỏi đáp | |
| 6.8 | Safety guard + rate limit | |

---

## Giai đoạn 7 — Tracking & Attendance

**Nguồn:** Logic mục 7, 8, 11, 12

| # | Việc |
|---|---|
| 7.1 | Migration `student_attendance_sessions`, `student_activity_logs` |
| 7.2 | Bắn 8 loại event từ frontend |
| 7.3 | Tính `effective_study_time` |
| 7.4 | Điểm danh 3 trạng thái (ngưỡng 70%) |
| 7.5 | Flow vắng mặt 4 bước (late → absent_pending → absent → auto-sinh) |
| 7.6 | `risk_score` 5 trọng số + phân mức xanh/vàng/đỏ |
| 7.7 | Cron: chốt attendance, tính risk score |

> ❓ Chốt **Q7** (7 hằng số chưa có giá trị) trước 7.3–7.6. Không có `X`, `Y`, ngưỡng idle, "100% chuẩn" thì không code được.

---

## Giai đoạn 8 — Parent Monitoring

**Nguồn:** Module 9 + Logic mục 9, 10

> ❓ Chốt **Q5** (1 phụ huynh nhiều con?) và **Q6** (ParentAccount riêng hay role?) trước khi migration.

| # | Việc |
|---|---|
| 8.1 | Migration `parent_accounts`, `parent_notifications` |
| 8.2 | Liên kết phụ huynh ↔ học sinh |
| 8.3 | Dashboard phụ huynh — đủ **6 mục** (Logic mục 9) |
| 8.4 | Thông báo mức cơ bản: bắt đầu buổi / hoàn thành / vắng |
| 8.5 | 5 rule cảnh báo (Logic mục 10) |
| 8.6 | Báo cáo cuối ngày / cuối tuần + cron |
| 8.7 | Kênh gửi: in-app → email → SMS/push |

---

## Giai đoạn 9 — Thanh toán

**Nguồn:** Module 11

> ❓ Chốt **Q8** trước: gói cước, chu kỳ, quyền nội dung theo gói. Hiện đặc tả chỉ ghi đúng 2 chữ "VNPAY, MOMO".

| # | Việc |
|---|---|
| 9.1 | Thiết kế gói + quyền truy cập |
| 9.2 | Tích hợp VNPAY |
| 9.3 | Tích hợp MOMO |
| 9.4 | Callback/IPN + đối soát |
| 9.5 | Lịch sử giao dịch, hóa đơn |

---

## Sơ đồ bảng dữ liệu

🔧 *Suy ra — cần review*

| Nhóm | Bảng | Giai đoạn |
|---|---|---|
| Auth | `users`, `password_resets`, `sessions` | G0 |
| Hồ sơ | `student_profiles` | G1 |
| Assessment | `assessments`, `assessment_questions`, `assessment_answers` | G2 |
| **Năng lực** | **`skill_profiles`** | G2 |
| Giáo trình | `curricula`, `curriculum_modules`, `lessons`, `exercises`, `quizzes`, `quiz_questions` | G3 |
| Tiến độ | `lesson_progress`, `quiz_attempts`, `quiz_answers` | G4 |
| Recommendation | `daily_recommendations` | G5 |
| AI | `conversations`, `messages`, `solver_requests`, `ai_logs` | G6 |
| Tracking | `student_attendance_sessions`, `student_activity_logs`, `risk_snapshots` | G7 |
| Phụ huynh | `parent_accounts`, `parent_notifications` | G8 |
| Thanh toán | `plans`, `subscriptions`, `payments` | G9 |

Ba bảng **in đậm / mới** so với blueprint gốc (`skill_profiles`, `risk_snapshots`, `daily_recommendations`) là các bảng Q10 đã chỉ ra là còn thiếu.

---

## Apache hay `artisan serve`?

🔧 *Suy ra*

Repo nằm trong `htdocs` nên có thể chạy qua Apache, nhưng Laravel đặt document root ở `public/`. Hai cách:

- **Dev:** `php artisan serve` (port 8000) — đơn giản, khuyến nghị.
- **Giống production:** trỏ VirtualHost của Apache vào `hoctoanonline/public`.

Đừng để lộ thư mục gốc qua `http://localhost/hoctoanonline` — như vậy `.env` nằm trong tầm truy cập của web.

---

## Ước lượng thô

🔧 *Suy ra — ước lượng tương đối, không phải cam kết*

| Giai đoạn | Khối lượng | Phụ thuộc |
|---|---|---|
| G0 Nền móng | S | — |
| G1 Hồ sơ | S | G0 |
| G2 Assessment | **L** | G1 + **Q1** |
| G3 Giáo trình | **L** | G2 |
| G4 Học & Quiz | M | G3 |
| G5 Recommendation | **L** | G4 + **Q4** |
| G6 AI Tutor/Solver | M | G0 + `gd` |
| G7 Tracking | M | G4 + **Q7** |
| G8 Parent | M | G7 + **Q5/Q6** |
| G9 Thanh toán | M | **Q8** |

G2, G3, G5 chiếm phần lớn công sức và rủi ro. Đây cũng chính là phần tạo ra giá trị — phần còn lại là CRUD.

---

## Việc cần làm ngay, trước khi code

1. **Chốt Q1** — thuật toán phân loại. Chặn G2, mà G2 chặn tất cả.
2. **Chốt Q11** — có làm vai trò teacher/admin không (đặc tả gốc không có).
3. **Chọn AI provider** — đặc tả nói "A.I" xuyên suốt nhưng không nói dùng gì.
4. **Bật `gd`** + khởi động MySQL.
5. **Cập nhật docs 01/04/05** — đang ghi Mongoose, giờ đã sai.
