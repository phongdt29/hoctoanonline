<?php

/*
 * Ticket F1 — DoD: config/hoctoan.php la nguon su that cho moi nguong nghiep vu.
 * CLAUDE.md quy tac #1: cam hardcode nguong. Test nay khoa cac gia tri goc
 * theo SPEC-AI-CODING §3 de khong ai doi nham.
 */

it('doc duoc thoi luong quiz tu config', function () {
    expect(config('hoctoan.quiz.duration_minutes'))->toBe(15);
});

it('co nguong phan loai hoc luc tang 1 theo spec §3.1', function () {
    expect(config('hoctoan.gpa_thresholds.trung_binh'))->toBe(5)
        ->and(config('hoctoan.gpa_thresholds.kha'))->toBe(8);
});

it('cau truc buoi hoc 20/60/20 cong lai bang 100', function () {
    $mix = config('hoctoan.session_mix');

    expect($mix['review'])->toBe(20)
        ->and($mix['new'])->toBe(60)
        ->and($mix['reinforce'])->toBe(20)
        ->and(array_sum($mix))->toBe(100);
});

it('trong so risk score cong lai bang 1.00 theo spec §3.6', function () {
    $weights = config('hoctoan.risk_weights');

    expect(array_sum($weights))->toEqualWithDelta(1.0, 0.0001)
        ->and($weights['absenteeism'])->toEqualWithDelta(0.30, 0.0001)
        ->and($weights['incomplete_session'])->toEqualWithDelta(0.20, 0.0001)
        ->and($weights['low_engagement'])->toEqualWithDelta(0.20, 0.0001)
        ->and($weights['quiz_decline'])->toEqualWithDelta(0.15, 0.0001)
        ->and($weights['missed_recommendation'])->toEqualWithDelta(0.15, 0.0001);
});

it('nguong attendance dung spec §3.5', function () {
    expect(config('hoctoan.attendance.present_ratio'))->toEqualWithDelta(0.70, 0.0001)
        ->and(config('hoctoan.attendance.late_after_min'))->toBe(15)
        ->and(config('hoctoan.attendance.absent_pending_after_min'))->toBe(30)
        ->and(config('hoctoan.attendance.idle_gap_minutes'))->toBe(3);
});

it('nguong risk chia 3 muc dung thu tu', function () {
    $levels = config('hoctoan.risk_levels');

    expect($levels['on_dinh'])->toBe(30)
        ->and($levels['can_theo_doi'])->toBe(60)
        ->and($levels['on_dinh'])->toBeLessThan($levels['can_theo_doi']);
});

it('solver gioi han toi da 2 hint va 20 anh/ngay theo spec §3.4', function () {
    expect(config('hoctoan.solver.max_hints'))->toBe(2)
        ->and(config('hoctoan.solver.image_per_day'))->toBe(20)
        ->and(config('hoctoan.solver.ocr_min_confidence'))->toBe(70);
});

it('reset token TTL 30 phut va ai timeout 60s', function () {
    expect(config('hoctoan.reset_token_ttl_min'))->toBe(30)
        ->and(config('hoctoan.ai_timeout'))->toBe(60);
});
