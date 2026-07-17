<?php
use App\Models\User;

beforeEach(fn () => $this->seed());

function s1Quiz() {
    $s = User::where('email','student1@hoctoan.test')->first()->student;
    $lesson = $s->activeCurriculum->modules->flatMap->lessons->first(fn($l) => $l->quiz !== null);
    $lesson->update(['status' => 'unlocked']);
    return [$s->user, $lesson];
}

it('trang quiz hien nut bat dau + timer', function () {
    [$user, $lesson] = s1Quiz();
    $this->actingAs($user)->get(route('quiz.show', $lesson->quiz))
        ->assertOk()
        ->assertSee('Bắt đầu làm quiz')
        ->assertSee('qz-timer', false);
});

it('nut Vào quiz trong lesson page link dung (khong phai href=#)', function () {
    [$user, $lesson] = s1Quiz();
    $lesson->update(['status' => 'unlocked']);
    $html = $this->actingAs($user)->get(route('lessons.show', $lesson))->getContent();
    expect($html)->toContain('/quiz/' . $lesson->quiz->id)
        ->and($html)->not->toContain('data-quiz-start');
});

it('khong vao duoc quiz cua lesson locked', function () {
    [$user, $lesson] = s1Quiz();
    $lesson->update(['status' => 'locked']);
    $this->actingAs($user)->get(route('quiz.show', $lesson->quiz))
        ->assertRedirect(route('curriculum'));
});

it('khong vao duoc quiz cua hoc sinh khac', function () {
    [, $lesson] = s1Quiz();
    $other = User::where('email','student8@hoctoan.test')->first();
    $other->student->update(['status' => 'learning']);
    $this->actingAs($other)->get(route('quiz.show', $lesson->quiz))->assertStatus(403);
});
