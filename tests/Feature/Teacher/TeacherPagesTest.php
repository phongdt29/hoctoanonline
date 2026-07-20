<?php
use App\Models\AssignmentSubmission;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\User;

beforeEach(fn () => $this->seed());

function teacher1(): User { return User::where('email','teacher1@hoctoan.test')->first(); }
function t1Class(): SchoolClass { return SchoolClass::where('teacher_id', teacher1()->id)->first(); }

it('trang danh sach lop hien lop cua giao vien', function () {
    $this->actingAs(teacher1())->get('/teacher/classes')
        ->assertOk()->assertSee('Toan 7 - Lop A')->assertSee('học sinh');
});

it('chi tiet lop hien hoc sinh + form giao bai', function () {
    $this->actingAs(teacher1())->get(route('teacher.class', t1Class()))
        ->assertOk()->assertSee('Học sinh trong lớp')->assertSee('Giao bài tập mới');
});

it('giao vien giao bai moi', function () {
    $class = t1Class();
    $this->actingAs(teacher1())->post(route('teacher.assignment.store', $class), [
        'title' => 'Bài tập test', 'content' => 'Làm bài 1-5',
        'due_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
    ])->assertRedirect();
    expect($class->assignments()->where('title','Bài tập test')->exists())->toBeTrue();
});

it('giao bai voi han qua khu -> loi', function () {
    $this->actingAs(teacher1())->post(route('teacher.assignment.store', t1Class()), [
        'title' => 'X', 'content' => 'Y', 'due_at' => now()->subDay()->format('Y-m-d\TH:i'),
    ])->assertSessionHasErrors('due_at');
});

it('trang cham bai hien danh sach hoc sinh', function () {
    $a = t1Class()->assignments()->first();
    $this->actingAs(teacher1())->get(route('teacher.assignment', $a))
        ->assertOk()->assertSee('Bài nộp của học sinh');
});

it('giao vien cham bai -> diem vao ledger', function () {
    $class = t1Class();
    $a = $class->assignments()->first();
    $student = $class->students->first();
    $sub = AssignmentSubmission::create([
        'assignment_id' => $a->id, 'student_id' => $student->id,
        'content' => 'Bài làm', 'submitted_at' => now(),
    ]);
    $this->actingAs(teacher1())->post(route('teacher.grade', $sub), ['score' => 8.5, 'feedback' => 'Tốt'])->assertRedirect();
    expect((float) $sub->fresh()->score)->toBe(8.5)
        ->and(PointLedger::where('reason','assignment_graded')->where('ref_id',$sub->id)->exists())->toBeTrue();
});

it('giao vien khac KHONG vao duoc lop nay', function () {
    $t2 = User::where('email','teacher2@hoctoan.test')->first();
    $this->actingAs($t2)->get(route('teacher.class', t1Class()))->assertStatus(403);
});

it('hoc sinh KHONG vao duoc trang giao vien', function () {
    $s = User::where('email','student1@hoctoan.test')->first();
    $this->actingAs($s)->get('/teacher/classes')->assertStatus(403);
});
