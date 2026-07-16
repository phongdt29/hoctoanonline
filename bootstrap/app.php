<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStudentAssessed;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ticket A3 — RBAC
        $middleware->alias([
            'role' => EnsureRole::class,
            'assessed' => EnsureStudentAssessed::class,
        ]);

        // Chua dang nhap -> ve trang login (khong phai route 'login' mac dinh cua Laravel)
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Ticket P4 — security headers cho moi web response.
        $middleware->web(append: [SecurityHeaders::class]);

        // Ticket R3 — IPN server-to-server (MOMO POST) khong co CSRF token.
        // An toan vi da verify signature trong PaymentService.
        $middleware->validateCsrfTokens(except: [
            'payment/momo-ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
