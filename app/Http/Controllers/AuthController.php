<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Управляет жизненным циклом аутентификации стандартными механизмами Laravel. */
class AuthController extends Controller
{
    /** Регистрирует обычного пользователя и отправляет подтверждение. */
    public function register(Request $request): mixed
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
        $data = $request->validate(['name' => 'required|string|max:100', 'last_name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => $this->passwordRules()]);
        $data['email'] = Str::lower($data['email']);
        $user = User::create($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->put('password_hash_web', Auth::guard()->hashPasswordForCookie($user->getAuthPassword()));
        $request->session()->regenerate();

        return redirect('/verify-email');
    }

    /** Проверяет пароль и обновляет идентификатор сессии. */
    public function login(Request $request): mixed
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $data['email'] = Str::lower($data['email']);
        if (! Auth::attempt([...$data, 'is_blocked' => false], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль, либо доступ приостановлен.']);
        }
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', Auth::guard()->hashPasswordForCookie($request->user()->getAuthPassword()));

        return redirect()->intended('/dashboard');
    }

    /** Завершает сессию и обновляет CSRF-токен. */
    public function logout(Request $request): mixed
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();

        return redirect('/');
    }

    /** Отправляет ссылку, не раскрывая существование аккаунта. */
    public function forgot(Request $request): mixed
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $data['email'] = Str::lower($data['email']);
        Password::sendResetLink($data);

        return back()->with('success', 'Если аккаунт существует, ссылка отправлена на почту.');
    }

    /** Меняет пароль по одноразовому токену. */
    public function reset(Request $request): mixed
    {
        $data = $request->validate(['email' => 'required|email', 'token' => 'required|string', 'password' => $this->passwordRules()]);
        $data['email'] = Str::lower($data['email']);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Ссылка недействительна или устарела.']);
        }

        return redirect('/login')->with('success', 'Пароль изменён. Войдите в аккаунт.');
    }

    /** Ограничивает пароль в байтах, чтобы bcrypt не усекал значимую часть. */
    private function passwordRules(): array
    {
        return [
            'required', 'string', 'confirmed', PasswordRule::min(12)->letters()->numbers(),
            function (string $attribute, mixed $value, \Closure $fail): void {
                // bcrypt использует максимум 72 байта и не принимает нулевые байты.
                if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                    $fail('Пароль должен занимать не более 72 байт и не содержать нулевых символов.');
                }
            },
        ];
    }
}
