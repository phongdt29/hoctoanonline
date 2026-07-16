<?php

use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Style guide (ticket F5)
|--------------------------------------------------------------------------
| Trang kiem tra design system. CHI local — UI-DESIGN-SPEC §6.5.
| Dat trong dieu kien environment de route KHONG TON TAI o production,
| thay vi chi an bang middleware.
|
| Phai co ca 'testing': phpunit.xml set APP_ENV=testing nen isLocal() = false,
| route se khong dang ky va test se an 404.
*/
if (app()->environment(['local', 'testing'])) {
    Route::get('/style-guide', function () {
        // Dung du lieu seed that (student1) de style guide phan anh dung thuc te.
        $student = Student::with('activeCurriculum')->first();
        $lessons = $student?->activeCurriculum?->lessons ?? collect();

        return view('style-guide', [
            'active'     => 'dashboard',
            'themeColor' => $student?->favorite_color,
            'lessons'    => $lessons,
            'justDoneId' => $lessons->firstWhere('status', 'unlocked')?->id,
        ]);
    })->name('style-guide');
}
