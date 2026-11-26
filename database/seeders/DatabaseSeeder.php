<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Создаёт исключительно вымышленные аккаунты без опубликованных паролей. */
class DatabaseSeeder extends Seeder
{
    /** Повторно использует аккаунты и запрещает выполнение вне локального или тестового окружения. */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Сидер разрешён только в local и testing.');
        }
        $password = config('demo.password');
        if (! is_string($password) || strlen($password) < 16) {
            throw new RuntimeException('Задайте уникальный DEMO_PASSWORD длиной минимум 16 символов в .env.');
        }
        foreach ([['admin@example.test', 'Александр', true], ['demo@example.test', 'Алексей', false]] as [$email, $name, $admin]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'name' => $name, 'last_name' => 'Демо', 'password' => $password,
                'city' => 'Казань', 'phone' => '+7 (000) 000-00-00',
                'situation' => 'Формирую финансовую подушку и планирую долгосрочные цели.',
                'strategy' => 'Регулярно пополнять накопления, распределять риски и пересматривать план раз в год.',
            ]);
            $user->forceFill(['is_admin' => $admin, 'email_verified_at' => now(), 'is_blocked' => false])->save();
        }
        for ($index = 1; $index <= 24; $index++) {
            $user = User::firstOrNew(['email' => "user{$index}@example.test"]);
            if (! $user->exists) {
                // Для фоновых записей нет общего доступного пароля, вход в них не требуется.
                $user->fill(['name' => 'Пользователь', 'last_name' => "Демо {$index}", 'password' => bin2hex(random_bytes(32)), 'city' => ['Казань', 'Москва', 'Самара'][$index % 3]]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }
    }
}
