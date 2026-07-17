<?php

/*
 * Panel tai khoan test tren trang login: hien o local, AN o production
 * (tranh lo tai khoan test khi deploy that).
 */

it('trang login hien panel tai khoan test o moi truong local', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Tài khoản test')
        ->assertSee('student1@hoctoan.test')
        ->assertSee('js-test-login', false);
});

it('panel tai khoan test AN o production', function () {
    config(['app.env' => 'production']);

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Tài khoản test')
        ->assertDontSee('js-test-login', false);
});
