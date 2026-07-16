<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ticket P4 — hardening: security headers cho moi response HTML.
 *
 * CSP cho phep CDN da dung (Bootstrap, jQuery, MathJax, fonts) — SPEC §6 nap qua CDN.
 * Neu them CDN moi phai cap nhat day.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // CSP chi ap cho HTML (khong ap cho JSON API — thua).
        if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Content-Security-Policy', $this->csp());
        }

        return $response;
    }

    private function csp(): string
    {
        $cdns = [
            'https://cdn.jsdelivr.net',
            'https://code.jquery.com',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
        ];
        $cdnList = implode(' ', $cdns);

        return implode('; ', [
            "default-src 'self'",
            // 'unsafe-inline' cho <style> mau ca nhan + script khoi tao Blade.
            "script-src 'self' 'unsafe-inline' {$cdnList}",
            "style-src 'self' 'unsafe-inline' {$cdnList}",
            "font-src 'self' {$cdnList}",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
