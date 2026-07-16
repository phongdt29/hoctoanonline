<?php

use App\Models\PointLedger;
use App\Models\Student;
use App\Models\User;

/*
 * Ticket F3 — DoD: point_ledger la APPEND-ONLY (CLAUDE.md quy tac #5).
 * Test chan ca 3 duong di, khong chi update() truc tiep.
 */

function makeStudent(): Student
{
    $user = User::create([
        'name'     => 'Hoc sinh test',
        'email'    => 'student.'.uniqid().'@hoctoan.test',
        'password' => 'password',
        'role'     => User::ROLE_STUDENT,
    ]);

    return Student::create([
        'user_id'             => $user->id,
        'full_name'           => 'Hoc sinh test',
        'date_of_birth'       => '2010-05-01',
        'address'             => 'Ha Noi',
        'phone'               => '0900000000',
        'school_name'         => 'THCS Test',
        'grade'               => 8,
        'self_assessed_level' => 'kha',
        'math_gpa'            => 7.5,
        'invite_code'         => strtoupper(substr(uniqid(), -8)),
    ]);
}

function makeEntry(Student $student): PointLedger
{
    return PointLedger::create([
        'student_id' => $student->id,
        'amount'     => 10,
        'reason'     => PointLedger::REASON_QUIZ_SCORE,
        'ref_id'     => 1,
    ]);
}

it('ghi duoc ban ghi moi (append van hoat dong)', function () {
    $entry = makeEntry(makeStudent());

    expect($entry->exists)->toBeTrue()
        ->and($entry->amount)->toBe(10)
        ->and($entry->created_at)->not->toBeNull();
});

it('chan update() truc tiep', function () {
    $entry = makeEntry(makeStudent());

    expect(fn () => $entry->update(['amount' => 999]))
        ->toThrow(RuntimeException::class, 'append-only');
});

it('chan delete()', function () {
    $entry = makeEntry(makeStudent());

    expect(fn () => $entry->delete())
        ->toThrow(RuntimeException::class, 'append-only');
});

it('chan ca duong vong: doi thuoc tinh roi save()', function () {
    $entry = makeEntry(makeStudent());

    // Duong di nay KHONG goi update() nen phai duoc chan boi event `updating`.
    $entry->amount = 999;

    expect(fn () => $entry->save())
        ->toThrow(RuntimeException::class, 'append-only');
});

it('chan Model::destroy()', function () {
    $entry = makeEntry(makeStudent());

    expect(fn () => PointLedger::destroy($entry->id))
        ->toThrow(RuntimeException::class, 'append-only');
});

it('du lieu khong doi sau khi cac lenh sua bi chan', function () {
    $student = makeStudent();
    $entry   = makeEntry($student);

    try {
        $entry->update(['amount' => 999]);
    } catch (RuntimeException) {
        // mong doi
    }

    expect(PointLedger::find($entry->id)->amount)->toBe(10);
});

it('bang khong co cot updated_at', function () {
    expect(Schema::hasColumn('point_ledger', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumn('point_ledger', 'created_at'))->toBeTrue();
});

it('dieu chinh diem phai ghi but toan nguoc thay vi sua', function () {
    $student = makeStudent();
    makeEntry($student);

    // Cach DUY NHAT de dieu chinh: ghi ban ghi moi voi amount am.
    PointLedger::create([
        'student_id' => $student->id,
        'amount'     => -10,
        'reason'     => PointLedger::REASON_ADMIN_ADJUSTMENT,
        'ref_id'     => null,
    ]);

    expect(PointLedger::where('student_id', $student->id)->sum('amount'))->toBe(0)
        ->and(PointLedger::where('student_id', $student->id)->count())->toBe(2);
});
