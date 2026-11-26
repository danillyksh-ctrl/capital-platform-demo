<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

/** Точка расширения общих сервисов приложения. */
class AppServiceProvider extends ServiceProvider
{
    /** Регистрирует зависимости приложения. */
    public function register(): void {}

    /** Настраивает сервисы после регистрации зависимостей. */
    public function boot(): void
    {
        Inertia::encryptHistory();
    }
}
