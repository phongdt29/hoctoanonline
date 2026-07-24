<?php

use App\Models\AiLog;
use App\Models\Student;
use App\Models\User;
use App\Services\Ai\DailyLimitException;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\AiProviderSeeder::class);
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]])]);
});

function limitStudent(): Student
{
    $u = User::create(['name' => 'HS', 'email' => 'l.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_STUDENT]);

    return Student::create([
        'user_id' => $u->id, 'full_name' => 'HS', 'date_of_birth' => '2012-01-01', 'address' => 'HN',
        'phone' => '0900000000', 'school_name' => 'X', 'grade' => 8, 'self_assessed_level' => 'kha',
        'math_gpa' => 7.0, 'status' => Student::STATUS_LEARNING, 'invite_code' => strtoupper(substr(uniqid(), -8)),
    ]);
}

it('cho phep dung 10 luot solver/tutor, chan luot thu 11', function () {
    config(['hoctoan.ai_calls_per_day' => 10]);
    $ai = app(AiProviderService::class);
    $s = limitStudent();

    // 10 luot dau (tron solver + tutor) deu qua.
    for ($i = 0; $i < 10; $i++) {
        $feature = $i % 2 === 0 ? AiLog::FEATURE_SOLVER : AiLog::FEATURE_TUTOR_CHAT;
        expect($ai->text($feature, 'de bai', $s->id))->toBe('ok');
    }

    // Luot 11 bi chan.
    expect(fn () => $ai->text(AiLog::FEATURE_SOLVER, 'de bai', $s->id))
        ->toThrow(DailyLimitException::class);
});

it('chuoi he thong (sinh giao trinh) KHONG bi gioi han du hoc sinh da het luot', function () {
    config(['hoctoan.ai_calls_per_day' => 3]);
    $ai = app(AiProviderService::class);
    $s = limitStudent();

    // Dung het 3 luot solver.
    for ($i = 0; $i < 3; $i++) {
        $ai->text(AiLog::FEATURE_SOLVER, 'x', $s->id);
    }
    // Solver luot 4 bi chan...
    expect(fn () => $ai->text(AiLog::FEATURE_SOLVER, 'x', $s->id))->toThrow(DailyLimitException::class);
    // ...nhung feature he thong (curriculum) van chay binh thuong.
    expect($ai->text(AiLog::FEATURE_CURRICULUM, 'x', $s->id))->toBe('ok');
});

it('limit = 0 thi tat gioi han', function () {
    config(['hoctoan.ai_calls_per_day' => 0]);
    $ai = app(AiProviderService::class);
    $s = limitStudent();

    for ($i = 0; $i < 15; $i++) {
        expect($ai->text(AiLog::FEATURE_SOLVER, 'x', $s->id))->toBe('ok');   // khong bi chan
    }
});
