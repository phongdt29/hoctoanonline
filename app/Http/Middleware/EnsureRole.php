<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ticket A3 — chan theo vai tro. Dung: ->middleware('role:teacher,admin')
 *
 * Day chi la lop chan THO theo route. Quyen tren TUNG BAN GHI (parent chi xem con
 * da link, teacher chi xem lop minh) phai dung Policy — middleware khong biet
 * ban ghi nao dang duoc truy cap.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
