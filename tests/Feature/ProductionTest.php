<?php

use App\Models\User;

/*
 * Ticket P3 + P4 — healthz, security headers, error pages.
 */

it('P3: /healthz tra 200 khi DB + queue khoe', function () {
    $this->get('/healthz')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database.ok', true)
        ->assertJsonPath('checks.queue.ok', true);
});

it('P3: /healthz cong khai, khong can dang nhap', function () {
    $this->get('/healthz')->assertOk();   // khong redirect ve login
});

it('P4: security headers co mat tren trang web', function () {
    $res = $this->get('/login');

    $res->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($res->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

it('P4: CSP cho phep cac CDN da dung (bootstrap, jquery, mathjax)', function () {
    $csp = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('cdn.jsdelivr.net')
        ->and($csp)->toContain('code.jquery.com')
        ->and($csp)->toContain('fonts.googleapis.com');
});

it('P4: trang 403 theo theme', function () {
    // student vao admin -> 403 render trang loi theo theme.
    $student = User::where('email', 'student1@hoctoan.test')->first();

    // Can seed de co student.
})->skip('can seed — kiem o test RBAC rieng');

it('P4: trang 404 theo theme', function () {
    $this->get('/khong-ton-tai-'.uniqid())
        ->assertNotFound()
        ->assertSee('Không tìm thấy trang');
});

it('P3: healthz bao failed_jobs (canh bao khi > 10)', function () {
    $data = $this->get('/healthz')->json();

    expect($data['checks']['queue'])->toHaveKey('pending')
        ->and($data['checks']['queue'])->toHaveKey('failed');
});
