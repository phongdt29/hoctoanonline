<?php

/*
|--------------------------------------------------------------------------
| Ngưỡng nghiệp vụ hoctoanonline
|--------------------------------------------------------------------------
| Nguồn sự thật cho MỌI con số nghiệp vụ. CLAUDE.md quy tắc #1: cấm hardcode
| ngưỡng trong Service/Controller/Job — luôn đọc từ file này.
|
| Tham chiếu spec: SPEC-AI-CODING-hoctoanonline-Laravel-v3.md §3
*/

return [

    /*
    | Tầng 1 phân loại học lực — CHỈ THAM KHẢO (SPEC §3.1).
    | Quyết định cuối là final_level do AI hiệu chỉnh ở tầng 2.
    | gpa <= trung_binh => 'trung_binh' | gpa <= kha => 'kha' | còn lại => 'gioi'
    */
    'gpa_thresholds' => [
        'trung_binh' => 5,
        'kha'        => 8,
    ],

    /*
    | Quiz cuối buổi (SPEC §2.5, §3.3)
    | new_lesson_min: >= điểm này => mở bài mới
    | review_min:     < điểm này => ưu tiên ôn lại trước
    */
    'quiz' => [
        'duration_minutes' => 15,
        'new_lesson_min'   => 8,
        'review_min'       => 5,
    ],

    /*
    | Cấu trúc buổi học 20/60/20 (SPEC §3.3) — tổng phải = 100
    */
    'session_mix' => [
        'review'    => 20,
        'new'       => 60,
        'reinforce' => 20,
    ],

    /*
    | Điểm danh & thời gian học thật (SPEC §3.5)
    | present_ratio:            effective >= 70% chuẩn buổi => present
    | late_after_min:           T+15' chưa vào => late
    | absent_pending_after_min: T+30' chưa vào => absent_pending
    | idle_gap_minutes:         gap giữa 2 event > 3' thì KHÔNG tính là active
    */
    'attendance' => [
        'present_ratio'            => 0.70,
        'late_after_min'           => 15,
        'absent_pending_after_min' => 30,
        'idle_gap_minutes'         => 3,
    ],

    /*
    | Learning Risk Score (SPEC §3.6) — tổng trọng số phải = 1.00
    */
    'risk_weights' => [
        'absenteeism'           => .30,
        'incomplete_session'    => .20,
        'low_engagement'        => .20,
        'quiz_decline'          => .15,
        'missed_recommendation' => .15,
    ],

    /*
    | Phân mức risk: 0..on_dinh = 🟢 | ..can_theo_doi = 🟡 | trên nữa = 🔴
    */
    'risk_levels' => [
        'on_dinh'      => 30,
        'can_theo_doi' => 60,
    ],

    /*
    | Solver — chống lệ thuộc đáp án (SPEC §3.4)
    */
    'solver' => [
        'max_hints'          => 2,
        'image_per_day'      => 20,
        'ocr_min_confidence' => 70,
    ],

    /*
    | Reset password: TTL token, one-time (SPEC §5, ticket A2)
    */
    'reset_token_ttl_min' => 30,

    /*
    | Timeout mọi call AI qua AiProviderService (SPEC §3.8)
    */
    'ai_timeout' => 60,

    /*
    | Cảnh báo phụ huynh (SPEC §3.7) — các ngưỡng còn lại của bảng trigger.
    | Tách riêng để không hardcode trong AttendanceService/RiskScoreService.
    */
    'parent_alerts' => [
        'inactive_flag_minutes'  => 10,  // vào nhưng không active 10' => flag nguy cơ bỏ buổi
        'absent_streak_high'     => 2,   // vắng 2 buổi liên tiếp => cảnh báo mức cao
        'quiz_decline_sessions'  => 3,   // quiz giảm 3 buổi liên tiếp => đề xuất theo dõi
    ],

    /*
    | RecommendationService — cửa sổ tín hiệu (SPEC §3.3)
    */
    'recommendation' => [
        'stability_window_sessions' => 5, // độ ổn định xét 3..5 buổi gần nhất
        'review_ratio_mid_score'    => 30, // 5..<8 => bài mới + 30% ôn
    ],

];
