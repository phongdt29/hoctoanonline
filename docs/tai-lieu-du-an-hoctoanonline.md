# TÀI LIỆU DỰ ÁN — HOCTOANONLINE.COM

**Nền tảng học toán online cá nhân hóa bằng A.I**

| Hạng mục | Nội dung |
|---|---|
| Tên hệ thống | Nền tảng học toán online cá nhân hóa bằng A.I (hoctoanonline.com) |
| Mục tiêu | Giúp học sinh cải thiện tư duy và điểm số thông qua lộ trình học cá nhân hóa |
| Đối tượng | Học sinh từ lớp 6 → lớp 12 |
| Công nghệ lõi | A.I tạo bài tập, hướng dẫn/gợi ý giải bài, phân tích học lực, gợi ý lộ trình, cá nhân hóa giáo án |
| Runtime dữ liệu | MongoDB / Mongoose (SQL chỉ là blueprint/legacy); có seed dữ liệu dev/staging |
| Phiên bản tài liệu | 1.0 — 15/07/2026 |

---

## 1. TỔNG QUAN & MỤC TIÊU HỆ THỐNG

Hệ thống phải làm được **5 việc chính**:

1. Lưu hồ sơ học sinh thật chi tiết.
2. Đánh giá đầu vào (assessment).
3. Tạo giáo trình cá nhân hóa.
4. Theo dõi tiến độ học và kiểm tra sau mỗi buổi.
5. Hỗ trợ hỏi đáp qua Chatbot cá nhân hóa (thầy/cô), giải bài và giải thích từng bước.

### User flow hoàn chỉnh

```
Đăng ký → Nhập thông tin cá nhân → Test đầu vào → AI phân tích
→ Tạo giáo trình → Dashboard → Học bài → Test 15 phút
→ AI gợi ý tiếp → Lặp lại
```

---

## 2. DANH SÁCH MODULE

| # | Module | Vai trò chính |
|---|---|---|
| 1 | Đăng ký người dùng | Thu thập dữ liệu đầu vào để AI cá nhân hóa |
| 2 | Kiểm tra đầu vào | Đánh giá năng lực, phân loại học lực |
| 3 | Tạo giáo trình A.I (Core Engine) | Sinh lộ trình học riêng cho từng học sinh |
| 4 | Tiến độ học (Dashboard) | Cho học sinh biết mình đang ở đâu |
| 5 | Kiểm tra cuối buổi | Quiz 15 phút, đánh giá ngay sau khi học |
| 6 | Gợi ý học tiếp (AI Recommendation) | Quyết định học mới / luyện thêm / ôn lại |
| 7 | Hỏi đáp & giải bài (AI Solver) | Giải bài step-by-step, không lộ đáp án ngay |
| 8 | Trang cá nhân (Personalization) | Theme, avatar AI, giao diện theo học sinh |
| 9 | Thông báo | Nhắc học, cảnh báo cho học sinh & phụ huynh |
| 10 | Parent Monitoring | Phụ huynh theo dõi việc học thực tế |
| 11 | Thanh toán | Cổng VNPAY, MOMO |

---

## 3. ĐẶC TẢ CHI TIẾT TỪNG MODULE

### 3.1. Module Đăng ký người dùng

**Mục tiêu:** thu thập dữ liệu đầu vào để A.I cá nhân hóa ngay từ đầu.

**Thông tin bắt buộc:**

| Trường dữ liệu | Mô tả / mục đích |
|---|---|
| Họ và tên | Xác định người dùng |
| Ngày sinh | Xác định độ tuổi |
| Địa chỉ nơi ở | Cá nhân hóa theo vùng miền, chương trình học |
| Email | Đăng nhập / thông báo |
| Số điện thoại | Xác thực + hỗ trợ |
| Trường học | Ngữ cảnh học tập |
| Khối lớp | Lớp 6 → 12 |
| Học lực | Tự đánh giá ban đầu |
| Điểm trung bình toán | Input cho AI |
| Chọn giáo viên | Chọn "thầy" hoặc "cô" (avatar AI) |
| Màu sắc yêu thích | Cá nhân hóa UI |
| Sở thích | Cá nhân hóa trải nghiệm |

### 3.2. Module Kiểm tra đầu vào

**Quy trình:**

1. A.I tạo bộ đề dựa trên: lớp + học lực tự khai + điểm trung bình.
2. Đề gồm 5–10 câu (trắc nghiệm + tự luận).
3. Học sinh làm bài trực tiếp trên hệ thống.
4. A.I chấm điểm + phân tích lỗi.

**Phân loại học lực (rule cứng):**

| Điểm trung bình | Xếp loại |
|---|---|
| ≤ 5 | Trung bình |
| ≤ 8 | Khá |
| > 8 | Giỏi |

**Output:** nhóm kiến thức yếu, tốc độ làm bài, mức độ hiểu — đây là nền tảng để tạo giáo trình.

### 3.3. Module Tạo giáo trình A.I (Core Engine)

**Mục tiêu:** tạo lộ trình học riêng cho từng học sinh.

**Thành phần:**

1. **Lý thuyết** — chia theo chuyên đề, tối giản, dễ hiểu, phù hợp học lực.
2. **Bài tập thực hành** — phân cấp độ Dễ → Trung bình → Khó, tự động điều chỉnh theo kết quả.
3. **Lộ trình học 4 giai đoạn:**

| Giai đoạn | Nội dung |
|---|---|
| 1 | Ôn lại nền tảng |
| 2 | Củng cố kiến thức |
| 3 | Nâng cao |
| 4 | Luyện đề |

**Giáo viên ảo (AI Tutor):**

| Chức năng | Mô tả |
|---|---|
| Chatbox | Hỏi đáp trực tiếp |
| Giải thích | Theo từng bước |
| Phong cách | Thầy / Cô tùy chọn |
| Cá nhân hóa | Theo trình độ học sinh |

### 3.4. Module Tiến độ học (Dashboard)

**Mục tiêu:** giúp học sinh biết mình đang ở đâu.

**Hiển thị:** % hoàn thành theo lộ trình, số buổi đã học, số buổi còn lại, điểm trung bình theo bài kiểm tra, nhóm kiến thức yếu (AI phân tích).

**Gợi ý học hôm nay:** AI dựa vào giáo trình + kết quả gần nhất + thời gian học → đưa ra bài học và bài tập cho hôm nay.

### 3.5. Module Kiểm tra cuối buổi

| Thành phần | Mô tả |
|---|---|
| Thời lượng | 15 phút |
| Nội dung | Theo bài học hôm đó |
| Chấm điểm | Tự động |

**Output:** điểm số, phân tích lỗi sai, đề xuất **Học tiếp** hoặc **Ôn lại**.

### 3.6. Module Gợi ý học tiếp (AI Recommendation)

| Trường hợp | Hành động |
|---|---|
| Điểm cao | Học bài mới |
| Điểm trung bình | Luyện thêm |
| Điểm thấp | Ôn lại |

### 3.7. Module Hỏi đáp & giải bài (AI Solver)

| Chức năng | Mô tả |
|---|---|
| Nhập đề | Text / ảnh (OCR) |
| Giải bài | Step-by-step |
| Giải thích | Dễ hiểu |
| Gợi ý | Không lộ đáp án ngay |
| Bài tương tự | Sinh bài để luyện thêm |

**Giá trị cốt lõi:** không chỉ cho đáp án, mà giúp học sinh hiểu cách làm.

### 3.8. Module Trang cá nhân (Personalization)

**Mục tiêu:** tăng engagement và trải nghiệm cá nhân.

| Thành phần | Mô tả |
|---|---|
| Theme màu | Theo màu yêu thích |
| Avatar AI | Thầy / cô |
| Dashboard riêng | Theo học lực |
| Giao diện | Cá nhân hóa |

**Cá nhân hóa nâng cao:** nội dung bài học theo sở thích, giao diện thân thiện với tính cách học sinh.

### 3.9. Module Thông báo

**Cho học sinh:**

| Chức năng | Mô tả |
|---|---|
| Nhắc học | Hằng ngày |
| Cảnh báo | Sắp quên bài |
| Gợi ý AI | Học tiếp |
| Streak | Duy trì chuỗi ngày học |

**Cho phụ huynh:**

| Chức năng | Mô tả |
|---|---|
| Nhắc lịch học | Hằng ngày |
| Thông báo vắng mặt | Nếu học sinh không đăng nhập, hoặc đăng nhập nhưng không học |
| % hoàn thành | Theo lộ trình |
| Buổi đã học / còn lại | Tổng số + kế hoạch |
| Điểm trung bình | Theo bài kiểm tra |
| Nhóm kiến thức yếu | AI phân tích |

### 3.10. Module Parent Monitoring

Đây là một module riêng, không chỉ là trang xem điểm. Phụ huynh cần thấy tối thiểu **6 nhóm thông tin**:

1. **Lịch học hôm nay** — buổi nào phải học, thời gian dự kiến.
2. **Trạng thái tham gia** — đã vào học chưa, đúng giờ hay trễ, có vắng không.
3. **Thời gian học thật** — học thực bao nhiêu phút, mức tập trung.
4. **Kết quả buổi học** — điểm quiz cuối buổi, nội dung đã hoàn thành.
5. **Cảnh báo bất thường** — vắng 2 buổi liên tiếp, học quá ít, điểm giảm mạnh, bỏ quiz nhiều lần.
6. **Gợi ý can thiệp** — cần nhắc học, cần đổi khung giờ, cần tăng ôn phần cũ.

### 3.11. Module Thanh toán

Tích hợp cổng thanh toán: **VNPAY** và **MOMO**.

---

## 4. ĐẶC TẢ LOGIC A.I

### 4.1. Logic phân loại học lực đầu vào

**Rule cứng (tầng 1):**

```python
if score <= 5:
    level = "trung_binh"
elif score <= 8:
    level = "kha"
else:
    level = "gioi"
```

**Tầng 2 — A.I hiệu chỉnh theo bài test thật**, kết hợp thêm:

- Điểm bài test đầu vào
- Thời gian làm bài
- Số lỗi sai theo chủ đề

> Điểm trung bình chỉ là tín hiệu tham khảo, không phải biến quyết định chính (xem mục 7.1).

### 4.2. Logic tạo đề kiểm tra đầu vào

| | Nội dung |
|---|---|
| **Input** | Khối lớp, học lực tự khai, điểm trung bình môn toán, trường học, kết quả các câu trước (adaptive) |
| **Output** | 5–10 câu, phủ đủ chủ đề nền tảng của lớp đó, độ khó vừa sức nhưng đủ để phân loại |

### 4.3. Logic tạo giáo trình

| | Nội dung |
|---|---|
| **Input** | Lớp hiện tại, học lực đầu vào, kỹ năng mạnh/yếu, thời lượng học dự kiến, mục tiêu cải thiện |
| **Output** | Giáo trình tổng thể → module → lesson theo buổi → bài tập từng buổi → quiz 15 phút cuối buổi |

### 4.4. Logic gợi ý bài học hôm nay

**AI sử dụng:** lesson còn lại trong curriculum, trạng thái hoàn thành lesson trước, điểm quiz cuối buổi gần nhất, phần kiến thức đang yếu, số buổi còn lại.

**Rule mẫu:**

| Điểm quiz | Hành động |
|---|---|
| ≥ 8 | Mở bài mới |
| 5 → dưới 8 | Học bài mới nhưng có 30% ôn lại |
| < 5 | Ưu tiên buổi ôn lại trước khi học tiếp |

**Cấu trúc buổi học khuyến nghị (adaptive):**

- 20% ôn phần dễ quên
- 60% bài mới phù hợp
- 20% củng cố lỗi sai gần đây

Thuật toán mỗi buổi phải trả lời đồng thời 3 câu: **học gì mới — ôn lại gì cũ — ưu tiên mức nào**, chứ không chỉ "buổi sau học gì".

### 4.5. Logic trang cá nhân

| | Nội dung |
|---|---|
| **Input** | Màu sắc yêu thích, sở thích, chọn học với thầy/cô, tóm tắt tính cách từ hành vi học |
| **Output** | Giao diện phù hợp, AI tutor mặc định, microcopy phù hợp phong cách học sinh |

### 4.6. Logic AI Solver (chống lệ thuộc đáp án)

Chatbox trả lời quá nhanh và quá đầy đủ sẽ khiến học sinh lệ thuộc đáp án. Nguyên tắc xử lý:

1. Ưu tiên **gợi mở trước**.
2. Chỉ bung **full lời giải khi học sinh thực sự cần**.
3. Theo dõi hành vi: học sinh đang **học** hay chỉ đang **xin đáp án**.

---

## 5. THEO DÕI THỜI GIAN HỌC & ĐIỂM DANH

### 5.1. Active Learning Time (thời gian học chủ động thực sự)

**Các tín hiệu cần ghi nhận:**

| Tín hiệu | Ý nghĩa |
|---|---|
| Vào lesson | Bắt đầu buổi học |
| Scroll / chuyển phần | Có xem nội dung |
| Click làm bài | Có tương tác |
| Trả lời câu hỏi | Có tham gia |
| Chat hỏi bài | Có học thật |
| Xin gợi ý | Đang cố giải |
| Nộp quiz | Hoàn thành một phần buổi |
| Idle quá lâu | Có thể bỏ học |
| Rời tab / minimize lâu | Nguy cơ không tập trung |

**Công thức:** `effective_study_time = tổng thời gian có tương tác hợp lệ`

**Ví dụ:** xem lý thuyết 7 phút (có scroll/click) + làm bài tập 12 phút + chat hỏi bài 4 phút + quiz 8 phút → **effective study time = 31 phút**.

Nếu mở app 90 phút nhưng chỉ có 8 phút tương tác thật, hệ thống phải báo: *online 90 phút — học thực 8 phút — mức tập trung thấp*.

### 5.2. Điểm danh 3 trạng thái

| Trạng thái | Ý nghĩa | Rule mẫu |
|---|---|---|
| **Present** | Vào học đúng giờ, đủ hoạt động tối thiểu | Vào buổi học + active learning time ≥ 70% chuẩn + có làm quiz cuối buổi |
| **Partial** | Có vào nhưng học chưa đủ | Active learning time < 70% hoặc bỏ quiz |
| **Absent** | Không vào hoặc gần như không tương tác | Không có active session đủ chuẩn |

### 5.3. Flow xử lý khi học sinh vắng mặt

1. **Bước 1:** Đến giờ học mà chưa vào hệ thống sau X phút → đánh dấu `late`.
2. **Bước 2:** Quá Y phút vẫn chưa vào → đánh dấu `absent pending`.
3. **Bước 3:** Hết khung giờ học mà không có active session đủ chuẩn → chốt `absent`.
4. **Bước 4:** Tự động sinh: thông báo phụ huynh, lesson bù, gợi ý thời gian học lại, cập nhật risk score.

---

## 6. THÔNG BÁO PHỤ HUYNH & LEARNING RISK SCORE

### 6.1. Cơ chế thông báo phụ huynh

**Mức cơ bản:** thông báo khi bắt đầu buổi học, khi hoàn thành buổi học, khi vắng mặt.

**Mức tốt hơn:** báo cáo cuối ngày, báo cáo cuối tuần, cảnh báo nguy cơ tụt tiến độ.

**Logic cảnh báo mẫu:**

| Tình huống | Hành động |
|---|---|
| Không vào học sau 15 phút từ giờ học | Gửi thông báo phụ huynh |
| Có vào nhưng không active trong 10 phút | Đánh dấu nguy cơ bỏ buổi |
| Không nộp quiz cuối buổi | Gửi cảnh báo chưa hoàn thành |
| Vắng 2 buổi liên tiếp | Cảnh báo mức cao |
| Điểm quiz giảm 3 buổi liên tiếp | Đề xuất phụ huynh theo dõi |

### 6.2. Learning Risk Score (rủi ro bỏ học / lệch tiến độ)

**Tín hiệu đầu vào:** số buổi vắng trong 7 ngày, thời lượng học thật trung bình, số lần bỏ quiz, tốc độ hoàn thành lesson, xu hướng điểm số, số lần hệ thống nhắc nhưng không học.

**Công thức mẫu:**

```
risk_score = 0.30 * absenteeism_rate
           + 0.20 * incomplete_session_rate
           + 0.20 * low_engagement_rate
           + 0.15 * quiz_decline_rate
           + 0.15 * missed_recommendation_rate
```

**Phân mức hiển thị cho phụ huynh:**

| Mức | Ý nghĩa | Màu |
|---|---|---|
| 0–30 | Ổn định | 🟢 Xanh |
| 31–60 | Cần theo dõi | 🟡 Vàng |
| 61–100 | Nguy cơ cao | 🔴 Đỏ |

---

## 7. CÁC VẤN ĐỀ GIẢI THUẬT & HƯỚNG CẢI TIẾN

### 7.1. Thuật toán đánh giá đầu vào quá đơn giản, dễ phân loại sai

**Vấn đề:** điểm trung bình cuối kỳ không phản ánh đúng năng lực thật — mỗi trường/giáo viên/đề thi có độ khó khác nhau; có học sinh điểm 8 nhưng hổng nền nặng, có học sinh điểm 6 nhưng tư duy tốt; học lực tự khai thường thiếu chính xác.

**Hệ quả:** phân loại sai ở bước đầu → toàn bộ phía sau lệch (đề đầu vào quá khó/dễ, giáo trình không vừa sức, gợi ý sai, học sinh chán, mất động lực).

**Cách sửa:** thuật toán tính theo nhiều lớp tín hiệu — điểm trung bình cuối kỳ, kết quả test đầu vào, thời gian làm từng câu, tỷ lệ sai theo từng chủ đề, độ ổn định giữa câu dễ và câu khó.

**Đầu ra cần có:** năng lực tổng quát, năng lực theo từng chuyên đề, mức độ tự học, tốc độ xử lý bài — **không chỉ 3 nhãn** trung bình/khá/giỏi.

### 7.2. Recommendation chưa đủ adaptive

**Vấn đề:** giáo trình có xu hướng "lập sẵn từ đầu" trong khi năng lực học sinh thay đổi liên tục. Nếu chỉ dựa vào điểm quiz cuối buổi + số buổi còn lại + giáo trình dựng sẵn, hệ thống sẽ: đẩy học sinh sang phần B khi đang yếu phần A, ôn lại quá ít/quá nhiều, gợi ý tuyến tính chứ không adaptive.

**Hệ quả:** "giả cá nhân hóa" — dashboard nhìn thông minh nhưng recommendation không chính xác, retention giảm vì học sinh thấy app "không hiểu mình".

**Cách sửa:** thuật toán gợi ý buổi sau phải chấm theo nhiều tín hiệu — điểm quiz, số lần xin gợi ý, số lần trả lời sai rồi sửa đúng, thời gian hoàn thành bài, dạng lỗi lặp lại, mức độ quên kiến thức cũ, độ ổn định trong 3–5 buổi gần nhất — và mỗi buổi có cấu trúc 20% ôn / 60% mới / 20% củng cố lỗi.

### 7.3. AI Solver có nguy cơ tạo lệ thuộc đáp án

Xử lý theo nguyên tắc gợi mở trước, chỉ bung full lời giải khi cần, và theo dõi hành vi học thật (chi tiết ở mục 4.6).

---

## 8. THIẾT KẾ DỮ LIỆU BỔ SUNG

Cần thêm tối thiểu **4 bảng/collection** để xử lý bài toán điểm danh — theo dõi — thông báo:

### 8.1. `student_attendance_sessions` — lưu phiên học

`student_id`, `lesson_id`, `scheduled_start_time`, `actual_start_time`, `actual_end_time`, `attendance_status`, `effective_study_minutes`, `idle_minutes`, `completion_rate`

### 8.2. `student_activity_logs` — lưu hành vi trong buổi học

`session_id`, `event_type`, `event_time`, `metadata`

Giá trị `event_type` ví dụ: `lesson_open`, `section_view`, `exercise_start`, `answer_submit`, `hint_request`, `chat_message`, `tab_inactive`, `quiz_submit`

### 8.3. `parent_accounts` — tài khoản phụ huynh liên kết học sinh

`full_name`, `phone`, `email`, `relation_to_student`, `linked_student_id`

### 8.4. `parent_notifications` — lịch sử thông báo gửi phụ huynh

`parent_id`, `student_id`, `notification_type`, `title`, `content`, `sent_at`, `read_at`

---

## 9. TRẠNG THÁI TRIỂN KHAI HIỆN TẠI

### 9.1. Gần hoàn thiện (tiến độ ≥ 85%)

| # | Hạng mục | Tiến độ | Ghi chú |
|---|---|---|---|
| 1 | Auth đăng nhập + RBAC theo vai trò | 90% | Đăng nhập, token, role redirect, RBAC audit đã ổn; thiếu reset password production. **Lỗi hiện tại: không đăng nhập/đăng ký được** |
| 9 | Solver text, safety, history, examples | 90% | Text solver, safety guard, history/examples đã có audit runtime |
| 16 | Content library, curriculum/lesson template | 90% | CRUD/route/template/approval có test tốt; AI generation production cần xác nhận |
| 25 | MongoDB/Mongoose runtime + seed dev/staging | 90% | Runtime dùng MongoDB/Mongoose; SQL là blueprint/legacy |
| 3 | Assessment đầu vào | 85% | Start/save/submit/result đã có, audit tốt; thiếu coverage toàn chuỗi sau assessment |
| 6 | Dashboard học sinh, progress, mastery, points | 85% | API/audit tốt; production analytics sâu còn hạn chế |
| 7 | Lesson, exercise, quiz | 85% | Thiếu browser test sâu và server-side anti-cheat/timer production |
| 11 | AI Tutor Chat | 85% | Conversation/message/chat runtime đã audit; reliability provider production cần kiểm chứng thêm |
| 12 | Reward points / point ledger | 85% | Ledger cho assessment/quiz/assignment/admin adjustment đã có; cần chốt release chính thức |
| 14 | Teacher classes, assignments, gradebook | 85% | Teacher API tốt; luồng học sinh tự nộp bài chưa rõ |

### 9.2. Đang hoàn thiện (45% – 80%)

| # | Hạng mục | Tiến độ | Ghi chú |
|---|---|---|---|
| 5 | Curriculum cá nhân hóa | 80% | Có generate/list/active/detail/module; thiếu cá nhân hóa động nâng cao |
| 17 | Admin/staff operations | 80% | Có users/classes/reports/activity/AI logs; admin settings/API contract production chưa sạch |
| 18 | Engagement, attendance, risk, fraud review | 80% | Có tracking/risk/fraud/audit review; thiếu production alerting/scheduler |
| 19 | AI governance, logs, safety | 80% | Có log/governance/safety; provider registry/CRUD backend root chưa đủ rõ |
| 2 | Đăng ký, onboarding, profile, theme, chọn tutor | 75% | Có UI/backend nền tảng; thiếu audit end-to-end cho đăng ký thật |
| 8 | Recommendation nâng cao | 70% | Cần kiểm chứng dữ liệu thật, dynamic adjustment, unlocking |
| 13 | Parent dashboard, children, notifications cơ bản | 70% | Thiếu weekly report, link parent-child qua UI/admin, email/SMS/push |
| 4 | Phân loại học sinh sau assessment | 60% | Có service/API; chưa chứng minh chạy tự động ổn định và nối sang curriculum |
| 10 | Solver image/OCR | 50% | Có upload/parse image; thiếu storage bền vững, confidence, rate-limit, audit production |
| 15 | Học sinh nhận/nộp teacher assignment | 45% | Có model/submission và grading; chưa đủ bằng chứng UI/API cho học sinh nộp bài thật |
| 20 | AI provider registry/CRUD | 45% | CRUD provider root chưa có coverage rõ |
| 24 | Backup, monitoring, production hardening | 45% | Có runbook/script; cần vận hành thật, backup restore drill, monitoring/alerting/HA |

### 9.3. Sẽ hoàn thiện — Roadmap (≤ 35%)

| # | Hạng mục | Tiến độ | Ghi chú |
|---|---|---|---|
| 21 | Notification platform đa kênh | 35% | Email/SMS/push/scheduler chưa hoàn chỉnh |
| 22 | Forgot/reset password production | 30% | Có UI, thiếu backend reset-token/email provider production |
| 23 | Weekly report phụ huynh | 30% | Thiếu report/scheduler/history/email |
| 29 | Analytics nâng cao/cohort/data warehouse | 25% | Dashboard cơ bản đã có |
| 26 | Gamification mở rộng: badge, streak, leaderboard | 20% | Reward points có rồi; gamification rộng hơn là roadmap |
| 27 | Mobile app | 0% | Mới trong kế hoạch mở rộng |
| 28 | Subscription/billing | 0% | Chưa có runtime billing; đang ở roadmap |

### 9.4. Vấn đề ưu tiên xử lý ngay

1. **Lỗi blocker: không đăng nhập / đăng ký được** (hạng mục 1) — chặn toàn bộ luồng người dùng.
2. **Forgot/reset password production** (hạng mục 22) — bắt buộc cho vận hành thật.
3. **Nối chuỗi assessment → phân loại → curriculum** (hạng mục 3, 4, 5) — đây là core value chain của sản phẩm.
4. **Luồng học sinh nộp teacher assignment** (hạng mục 15) — đang thiếu bằng chứng UI/API.
5. **Backup / monitoring / production hardening** (hạng mục 24) — điều kiện tối thiểu trước khi go-live.

---

## 10. PHỤ LỤC — TÓM TẮT KIẾN TRÚC VẬN HÀNH

- **Vai trò người dùng (RBAC):** Học sinh, Phụ huynh, Giáo viên, Admin/Staff.
- **Chuỗi giá trị lõi:** Đăng ký → Assessment → Phân loại → Curriculum cá nhân hóa → Học/Quiz → Recommendation → lặp lại.
- **Hệ AI:** tạo đề, chấm/phân tích, sinh giáo trình, tutor chat, solver (text + ảnh), recommendation, governance/safety/logs, provider registry.
- **Hệ giám sát:** attendance 3 trạng thái, activity logs, effective study time, risk score, parent notification.
- **Thanh toán:** VNPAY, MOMO (roadmap billing/subscription).
- **Hạ tầng dữ liệu:** MongoDB/Mongoose (runtime), seed dev/staging; bổ sung 4 collection cho attendance/monitoring.
