<?php
it('trang home hien thi landing page gioi thieu dich vu', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('lộ trình riêng', false)
        ->assertSee('Đăng ký miễn phí')
        // Tieu de co <span> gradient chen giua nen dung assertSeeText (bo tag roi so).
        ->assertSeeText('Chỉ 4 bước để bắt đầu')
        ->assertSee('Giải bài từ ảnh');
});

it('trang home co CTA dang ky va dang nhap', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toContain(route('register'))
        ->and($html)->toContain(route('login'));
});

it('user da dang nhap vao / thi redirect vao app', function () {
    $this->seed();
    $user = \App\Models\User::where('email', 'student1@hoctoan.test')->first();
    $this->actingAs($user)->get('/')->assertRedirect();
});
