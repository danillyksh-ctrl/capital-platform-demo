<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Проверяет полные HTTP-сценарии кабинета и границы доступа. */
class CabinetTest extends TestCase
{
    use RefreshDatabase;

    /** Создаёт проверенного пользователя для сценария. */
    private function member(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /** Регистрация создаёт обычного пользователя и отправляет письмо. */
    public function test_registration_hashes_password_and_ignores_admin_injection(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Тест', 'last_name' => 'Демо', 'email' => 'new@example.test', 'password' => 'StrongPassword123!', 'password_confirmation' => 'StrongPassword123!', 'is_admin' => true])->assertRedirect('/verify-email');
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** Слабый пароль не создаёт аккаунт. */
    public function test_registration_validates_input(): void
    {
        $this->post('/register', ['name' => 'Тест', 'last_name' => 'Демо', 'email' => 'wrong', 'password' => '123', 'password_confirmation' => '123'])->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    /** Вход и выход меняют состояние аутентификации. */
    public function test_login_and_logout(): void
    {
        $user = $this->member(['password' => 'StrongPassword123!']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    /** Частые неуспешные входы ограничиваются. */
    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'no@example.test', 'password' => 'wrong']);
        } $this->post('/login', ['email' => 'no@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    /** Неподтверждённая почта закрывает кабинет. */
    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $this->actingAs($this->member(['email_verified_at' => null]))->get('/dashboard')->assertRedirect('/verify-email');
    }

    /** Подписанная ссылка подтверждает email. */
    public function test_email_verification_requires_signature(): void
    {
        $user = $this->member(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect('/dashboard');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** Восстановление пароля отправляет и принимает одноразовый токен. */
    public function test_password_reset(): void
    {
        Notification::fake();
        $user = $this->member();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewStrongPassword123!', 'password_confirmation' => 'NewStrongPassword123!'];
        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewStrongPassword123!', $user->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    /** Редактирование сохраняет профиль без изменения ролей. */
    public function test_profile_update_and_completion(): void
    {
        $user = $this->member();
        $this->actingAs($user)->patch('/profile', ['name' => 'Новое имя', 'last_name' => 'Демо', 'phone' => '+7 000 0000000', 'city' => 'Казань', 'birth_date' => '1990-02-10', 'situation' => 'Тест', 'strategy' => 'План', 'is_admin' => true])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('Казань', $user->city);
        $this->assertFalse($user->is_admin);
        $this->assertSame(100, $user->completion());
    }

    /** Неверные данные не попадают в профиль. */
    public function test_profile_rejects_future_date_and_oversized_text(): void
    {
        $this->actingAs($this->member())->patch('/profile', ['name' => 'Тест', 'birth_date' => '2099-01-01', 'situation' => str_repeat('x', 5001), 'phone' => 'abc'])->assertSessionHasErrors(['birth_date', 'situation', 'phone']);
    }

    /** Гости и обычные пользователи не имеют доступа к администрированию. */
    public function test_admin_access_is_checked_on_server(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $user = $this->member();
        $target = $this->member();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->patch('/admin/users/'.$target->id.'/block')->assertForbidden();
        $this->assertFalse($target->fresh()->is_blocked);
    }

    /** Блокировка ведёт журнал и закрывает существующую сессию. */
    public function test_blocking_is_audited_and_revokes_access(): void
    {
        $admin = $this->member(['is_admin' => true]);
        $user = $this->member(['password' => 'StrongPassword123!']);
        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/block')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('admin_actions', ['actor_id' => $admin->id, 'subject_id' => $user->id, 'action' => 'blocked']);
        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword123!'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/block');
        $this->assertFalse($user->fresh()->is_blocked);
        $this->assertDatabaseHas('admin_actions', ['subject_id' => $user->id, 'action' => 'unblocked']);
    }

    /** Администратора невозможно заблокировать через HTTP. */
    public function test_administrators_cannot_be_blocked(): void
    {
        $admin = $this->member(['is_admin' => true]);
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/block')->assertForbidden();
    }

    /** Список поддерживает поиск, пагинацию и безопасную сортировку. */
    public function test_admin_search_pagination_and_sort_validation(): void
    {
        $admin = $this->member(['is_admin' => true]);
        User::factory()->count(15)->create();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn ($page) => $page->component('Admin')->has('users.data', 10));
        $this->get('/admin?search='.urlencode($admin->email))->assertInertia(fn ($page) => $page->has('users.data', 1));
        $this->get('/admin?sort=password')->assertSessionHasErrors('sort');
    }

    /** Выгрузка исключает пароль и содержит собственные данные. */
    public function test_export_contains_only_own_nonsecret_data(): void
    {
        $user = $this->member();
        $response = $this->actingAs($user)->get('/profile/export')->assertDownload('capital-profile.json');
        $data = json_decode($response->streamedContent(), true);
        $this->assertSame($user->email, $data['profile']['email']);
        $this->assertArrayNotHasKey('password', $data['profile']);
        $this->assertArrayNotHasKey('remember_token', $data['profile']);
    }

    /** Удаление требует пароль и уничтожает аккаунт. */
    public function test_account_deletion_requires_password(): void
    {
        $user = $this->member(['password' => 'StrongPassword123!']);
        $this->actingAs($user)->delete('/profile', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->delete('/profile', ['password' => 'StrongPassword123!'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    /** Неправильная подпись не подтверждает email. */
    public function test_verification_rejects_unsigned_link(): void
    {
        $user = $this->member(['email_verified_at' => null]);
        $this->actingAs($user)->get('/verify-email/'.$user->id.'/'.sha1($user->email))->assertForbidden();
    }

    /** HTML-поля передаются как данные, браузер получает CSP. */
    public function test_dashboard_has_security_headers(): void
    {
        $this->actingAs($this->member(['situation' => '<script>alert(1)</script>']))->get('/dashboard')->assertOk()->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
    }
}
