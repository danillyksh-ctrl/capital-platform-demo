<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/** Обрабатывает собственные данные пользователя. */
class ProfileController extends Controller
{
    /** Сохраняет только валидированные поля без прав администратора. */
    public function update(UpdateProfileRequest $request): mixed
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Профиль сохранён.');
    }

    /** Экспортирует белый список персональных полей без секретов. */
    public function export(Request $request): mixed
    {
        return response()->streamDownload(function () use ($request) {
            echo json_encode(['exported_at' => now()->toIso8601String(), 'profile' => $request->user()->only(['name', 'last_name', 'email', 'phone', 'city', 'birth_date', 'situation', 'strategy', 'created_at'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'capital-profile.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']);
    }

    /** Удаляет аккаунт после повторной проверки пароля. */
    public function destroy(Request $request): mixed
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return redirect('/')->with('success', 'Аккаунт удалён.');
    }
}
