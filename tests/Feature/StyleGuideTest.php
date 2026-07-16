<?php

use App\Support\ThemeColor;

/*
 * Ticket F5 — DoD ban Bootstrap (UI-DESIGN-SPEC §6):
 *   style-guide du component · doi --ht-primary mot cho -> tat ca doi theo ·
 *   theme.css khong phinh · thu tu nap asset dung.
 */

beforeEach(function () {
    $this->seed();
});

it('DoD F5: style-guide render duoc va co du 7 component', function () {
    $html = $this->get('/style-guide')->assertOk()->getContent();

    expect($html)
        ->toContain('card')              // 1. card
        ->toContain('num')               // 2. stat
        ->toContain('badge rounded-pill') // 3. status-chip
        ->toContain('ht-mastery')        // 4. mastery-grid
        ->toContain('list-group-item-action') // 5. quiz-option
        ->toContain('ht-bubble')         // 6. chat-bubble
        ->toContain('bi-journal-x');     // 7. empty-state
});

it('style-guide chi ton tai o local/testing, khong lo ra production', function () {
    // Route nam trong if(app()->environment(['local','testing'])) nen o production
    // no KHONG duoc dang ky — an toan hon la dang ky roi an bang middleware.
    expect(app()->environment(['local', 'testing']))->toBeTrue()
        ->and(Route::has('style-guide'))->toBeTrue()
        ->and(app()->isProduction())->toBeFalse();
});

it('theme.css khong vuot 250 dong (UI spec §2.3)', function () {
    $lines = count(file(public_path('css/theme.css')));

    expect($lines)->toBeLessThanOrEqual(250);
});

it('nap asset dung thu tu: bootstrap TRUOC theme.css', function () {
    $html = $this->get('/style-guide')->getContent();

    $bootstrapPos = strpos($html, 'bootstrap@5.3.3/dist/css/bootstrap.min.css');
    $themePos     = strpos($html, 'css/theme.css');
    $inlinePos    = strpos($html, '--ht-primary:');

    // theme.css phai sau bootstrap, va mau ca nhan phai sau theme.css.
    expect($bootstrapPos)->toBeLessThan($themePos)
        ->and($themePos)->toBeLessThan($inlinePos);
});

it('khong nap Vite/Tailwind/React (CLAUDE.md cam)', function () {
    $html = $this->get('/style-guide')->getContent();

    expect($html)
        ->not->toContain('/build/assets')
        ->not->toContain('tailwind')
        ->not->toContain('react');
});

it('dung Bootstrap Icons chu khong phai Lucide (UI spec §1 ghi de PLAN)', function () {
    $html = $this->get('/style-guide')->getContent();

    expect($html)->toContain('bootstrap-icons')
        ->and($html)->not->toContain('lucide');
});

it('mau ca nhan hoa chi nhan gia tri trong bang 10 mau', function () {
    expect(ThemeColor::allowedHexes())->toHaveCount(10);

    // Mau hop le -> giu nguyen
    expect(ThemeColor::resolve('#2563EB')['hex'])->toBe('#2563EB');

    // Mau ngoai bang -> ve mac dinh, KHONG in thang vao CSS
    expect(ThemeColor::resolve('#ff0000')['hex'])->toBe(config('hoctoan.personalization.default_color'));
});

it('chan XSS qua favorite_color: chuoi doc hai khong lot vao <style>', function () {
    $payload = '#fff}</style><script>alert(1)</script>';

    expect(ThemeColor::resolve($payload)['hex'])
        ->toBe(config('hoctoan.personalization.default_color'))
        ->and(ThemeColor::resolve($payload)['hex'])->not->toContain('<script>');
});

it('moi mau trong bang deu co rgb di kem (thieu rgb thi doi mau khong lan het)', function () {
    foreach (config('hoctoan.personalization.colors') as $color) {
        expect($color)->toHaveKeys(['name', 'hex', 'rgb'])
            ->and($color['hex'])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($color['rgb'])->toMatch('/^\d{1,3},\d{1,3},\d{1,3}$/');
    }
});

it('style-guide dung du lieu seed that cua student1', function () {
    $html = $this->get('/style-guide')->getContent();

    // Mastery grid phai ve 18 o = 18 lesson cua student1 (13 done + 1 now + 4 locked)
    expect(substr_count($html, 'class="cell'))->toBe(18);
});
