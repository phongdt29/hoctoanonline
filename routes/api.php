<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API AJAX — SPEC §5
|--------------------------------------------------------------------------
| Prefix /api/v1, tra JSON { data, message }, bao ve bang auth:sanctum.
| Cac endpoint that duoc bo sung dan theo ticket C3..T3.
|
| Luu y: file nay `install:api` le ra phai tao nhung khong tao — dung tay.
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ],
            'message' => 'OK',
        ]);
    })->name('api.me');
});
