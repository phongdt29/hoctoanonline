<?php
use App\Models\Student;
use App\Models\User;

beforeEach(fn () => $this->seed());

function onboardedStudent(): User {
    $u = User::create(['name'=>'HS','email'=>'skip'.rand(1000,9999).'@t.test','password'=>'password','role'=>'student']);
    Student::create(['user_id'=>$u->id,'full_name'=>'HS','grade'=>7,'self_assessed_level'=>'kha','math_gpa'=>6.5,'status'=>'onboarded','invite_code'=>'HT'.strtoupper(substr(uniqid(),-6))]);
    return $u;
}

it('nut Bo qua bai test hien tren trang assessment o local', function () {
    $u = onboardedStudent();
    $this->actingAs($u)->get('/assessment')
        ->assertOk()
        ->assertSee('Bỏ qua bài test');
});

it('bo qua bai test -> sinh lo trinh demo + vao dashboard', function () {
    $u = onboardedStudent();

    $this->actingAs($u)->post('/assessment/skip')->assertRedirect(route('dashboard'));

    $s = $u->student->fresh();
    expect($s->status)->toBe(Student::STATUS_LEARNING)
        ->and($s->activeCurriculum)->not->toBeNull()
        ->and($s->activeCurriculum->lessons->count())->toBeGreaterThan(0)
        ->and($s->latestClassification)->not->toBeNull();

    // Co it nhat 1 lesson unlocked de vao hoc ngay.
    expect($s->activeCurriculum->lessons->where('status','unlocked')->count())->toBeGreaterThanOrEqual(1);
});

it('sau khi bo qua -> vao duoc dashboard/curriculum (khong bi day ve assessment)', function () {
    $u = onboardedStudent();
    $this->actingAs($u)->post('/assessment/skip');

    $this->actingAs($u)->get('/dashboard')->assertOk();
    $this->actingAs($u)->get('/curriculum')->assertOk()->assertSee('demo');
});

it('bo qua 2 lan khong tao lo trinh trung', function () {
    $u = onboardedStudent();
    $this->actingAs($u)->post('/assessment/skip');
    $c1 = $u->student->fresh()->activeCurriculum->id;
    $this->actingAs($u)->post('/assessment/skip');
    $c2 = $u->student->fresh()->activeCurriculum->id;
    expect($c2)->toBe($c1)
        ->and(\App\Models\Curriculum::where('student_id',$u->student->id)->count())->toBe(1);
});

it('moi lesson demo co 3 exercise + quiz', function () {
    $u = onboardedStudent();
    $this->actingAs($u)->post('/assessment/skip');
    $u->student->fresh()->activeCurriculum->lessons->each(function ($l) {
        expect($l->exercises)->toHaveCount(3)->and($l->quiz)->not->toBeNull();
    });
});
