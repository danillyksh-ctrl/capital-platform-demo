<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Добавляет браузерные ограничения для демонстрационного кабинета. */
class SecurityHeaders
{
    /** Ограничивает источники скриптов, встраивание и кеширование персональных данных. */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
