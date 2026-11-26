<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Проверяет изоляцию аккаунтов и недоверенные параметры HTTP-запросов. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Подмена идентификатора не позволяет читать или менять чужой профиль. */
    public function test_profile_reads_and_updates_only_authenticated_user(): void
    {
        $owner = User::factory()->create(['situation' => 'Только владелец']);
        $other = User::factory()->create(['situation' => 'Чужая конфиденциальная ситуация']);
        $this->actingAs($owner)->get('/profile?user_id='.$other->id)
            ->assertInertia(fn ($page) => $page->where('auth.user.id', $owner->id)
                ->where('auth.user.situation', 'Только владелец'));
        $this->patch('/profile', [
            'name' => 'Обновлено', 'id' => $other->id, 'user_id' => $other->id,
            'email' => $other->email, 'is_admin' => true, 'is_blocked' => true,
            'email_verified_at' => null, 'password' => 'InjectedPassword123!',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Обновлено', $owner->fresh()->name);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertSame($owner->email, $owner->fresh()->email);
        $this->assertFalse($owner->fresh()->is_admin);
        $this->assertFalse($owner->fresh()->is_blocked);
        $this->assertSame($owner->password, $owner->fresh()->password);
        $export = $this->get('/profile/export?user_id='.$other->id)->streamedContent();
        $this->assertStringContainsString($owner->email, $export);
        $this->assertStringNotContainsString($other->email, $export);
        $this->assertStringNotContainsString($other->situation, $export);
        $this->get('/profile/'.$other->id)->assertNotFound();
        $this->patch('/admin/users/999999999/block')->assertForbidden();
    }

    /** Чужой идентификатор не меняет цель удаления аккаунта. */
    public function test_delete_cannot_target_another_user(): void
    {
        $owner = User::factory()->create(['password' => 'OwnerTestPassword123!']);
        $other = User::factory()->create();
        $this->actingAs($owner)->delete('/profile', ['password' => 'OwnerTestPassword123!', 'user_id' => $other->id, 'id' => $other->id])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    /** Гость не может выгружать данные, редактировать или удалять профиль. */
    public function test_guest_cannot_access_personal_data_endpoints(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->get('/profile/export')->assertRedirect('/login');
        $this->patch('/profile', ['name' => 'Взлом'])->assertRedirect('/login');
        $this->delete('/profile')->assertRedirect('/login');
    }

    /** Изменение хеша пароля отзывает ранее созданную сессию. */
    public function test_password_change_revokes_existing_session(): void
    {
        $user = User::factory()->create();
        $oldHash = Auth::guard()->hashPasswordForCookie($user->password);
        $user->update(['password' => 'ChangedTestPassword123!']);
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash])
            ->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    /** Подписанная ссылка другого пользователя не подтверждает почту. */
    public function test_email_verification_cannot_target_another_user(): void
    {
        $owner = User::factory()->create(['email_verified_at' => null]);
        $other = User::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->actingAs($owner)->get($url)->assertForbidden();
        $this->assertNull($other->fresh()->email_verified_at);
    }

    /** Регистрация не допускает дубликат с другим регистром и массив вместо email. */
    public function test_registration_rejects_case_duplicate_and_array_email(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'existing@example.test']);
        $payload = ['name' => 'Демо', 'last_name' => 'Тест', 'email' => 'EXISTING@example.test', 'password' => 'RegistrationTest123!', 'password_confirmation' => 'RegistrationTest123!'];
        $this->post('/register', $payload)->assertSessionHasErrors('email');
        $payload['email'] = ['invalid'];
        $this->post('/register', $payload)->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    /** Администратор получает только разрешённые колонки таблицы пользователей. */
    public function test_admin_payload_excludes_password_and_private_profile_text(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['situation' => 'Закрытый текст', 'strategy' => 'Закрытая стратегия']);
        $this->actingAs($admin)->get('/admin')->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'created_at')->where('filters.direction', 'desc')
            ->missing('users.data.0.password')->missing('users.data.0.remember_token')
            ->missing('users.data.0.situation')->missing('users.data.0.strategy'));
    }

    /** Сидер не работает с отсутствующим локальным паролем. */
    public function test_seeder_requires_private_configuration(): void
    {
        config(['demo.password' => null]);
        $this->expectException(\RuntimeException::class);
        $this->seed(DatabaseSeeder::class);
    }

    /** Входные пароли не вызывают усечение или ошибку bcrypt. */
    public function test_password_validation_rejects_bcrypt_edge_cases(): void
    {
        foreach ([str_repeat('LongPassword1', 8), "PasswordWith\0Null123!"] as $password) {
            $this->post('/register', [
                'name' => 'Демо', 'last_name' => 'Тест', 'email' => 'password-edge@example.test',
                'password' => $password, 'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }
}
