<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/** Передаёт только разрешённые публичные поля текущего пользователя. */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Общие данные страниц без пароля и токенов. */
    public function share(Request $request): array
    {
        return [...parent::share($request), 'auth' => ['user' => $request->user()?->only(['id', 'name', 'last_name', 'email', 'phone', 'city', 'birth_date', 'situation', 'strategy', 'is_admin', 'email_verified_at'])], 'completion' => $request->user()?->completion(), 'flash' => ['success' => fn () => $request->session()->get('success')]];
    }
}
