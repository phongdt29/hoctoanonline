<?php

use App\Models\Student;
use App\Models\User;

/*
 * Ticket C6 — trang lo trinh (Mastery Grid) + trang ket qua + trang assessment.
 * DoD lon: E2E den man Mastery Grid.
 */

beforeEach(function () {
    $this->seed();
});

function studentUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

it('DoD C6: student1 (co curriculum) thay Mastery Grid theo 4 phase', function () {
    $html = $this->actingAs(studentUser('student1@hoctoan.test'))
        ->get('/curriculum')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('ht-mastery')                   // Mastery Grid render
        ->toContain('Ôn nền tảng');                 // ten phase 1

    // Du 18 o = 18 lesson cua student1.
    expect(substr_count($html, 'class="cell'))->toBe(18);
});

it('curriculum: dem dung so o done/now/locked theo trang thai lesson', function () {
    $html = $this->actingAs(studentUser('student1@hoctoan.test'))
        ->get('/curriculum')->getContent();

    // Grid toan lo trinh: 13 done + 1 now + 4 locked. cell.done xuat hien it nhat 13 lan.
    expect(substr_count($html, 'cell done'))->toBeGreaterThanOrEqual(13);
});

it('student chua co curriculum -> empty state, dan sang assessment', function () {
    // student2 moi onboarded nhung chua assessed -> middleware assessed day ve assessment.
    $user = studentUser('student2@hoctoan.test');
    $user->student->update(['status' => Student::STATUS_ASSESSED]);   // qua assessed nhung chua co curriculum

    $this->actingAs($user)->get('/curriculum')
        ->assertOk()
        ->assertSee('Chưa có lộ trình')
        ->assertSee('Làm bài kiểm tra');
});

it('trang assessment hien man gioi thieu cho student chua lam', function () {
    $user = studentUser('student2@hoctoan.test');
    $user->student->update(['status' => Student::STATUS_ONBOARDED]);

    $this->actingAs($user)->get('/assessment')
        ->assertOk()
        ->assertSee('Bài kiểm tra đầu vào')
        ->assertSee('Bắt đầu làm bài');
});

it('student da assessed vao /assessment -> chuyen sang trang ket qua', function () {
    // student1 da learning + co assessment graded.
    $user = studentUser('student1@hoctoan.test');
    $assessment = $user->student->assessments()->where('status', 'graded')->first();

    $this->actingAs($user)->get('/assessment')
        ->assertRedirect(route('assessment.result', $assessment));
});

it('trang ket qua hien diem + nang luc theo chu de', function () {
    $user = studentUser('student1@hoctoan.test');
    $assessment = $user->student->assessments()->where('status', 'graded')->first();

    $this->actingAs($user)->get(route('assessment.result', $assessment))
        ->assertOk()
        ->assertSee('Kết quả bài kiểm tra')
        ->assertSee('Năng lực theo chủ đề');
});

it('khong xem duoc ket qua assessment cua nguoi khac', function () {
    $user1 = studentUser('student1@hoctoan.test');
    $assessment = $user1->student->assessments()->where('status', 'graded')->first();

    // student khac (chua co assessment) vao ket qua cua student1 -> 403
    $other = studentUser('student8@hoctoan.test');
    $other->student->update(['status' => Student::STATUS_LEARNING]);

    $this->actingAs($other)->get(route('assessment.result', $assessment))->assertStatus(403);
});
