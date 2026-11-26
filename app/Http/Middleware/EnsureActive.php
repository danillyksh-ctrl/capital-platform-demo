<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/** Отзывает доступ заблокированного пользователя на каждом запросе. */
class EnsureActive
{
    /** Завершает уже существующую сессию после блокировки. */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()?->is_blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Inertia::clearHistory();

            return redirect()->route('login')->withErrors(['email' => 'Доступ к аккаунту приостановлен.']);
        }

        return $next($request);
    }
}
