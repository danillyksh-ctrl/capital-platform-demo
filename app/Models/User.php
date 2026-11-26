<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Демонстрационный пользователь с профилем и серверными правами. */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'last_name', 'email', 'password', 'phone', 'city', 'birth_date', 'situation', 'strategy'];

    protected $hidden = ['password', 'remember_token'];

    /** Преобразует типы и хеширует пароль при записи. */
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'birth_date' => 'date:Y-m-d', 'is_admin' => 'boolean', 'is_blocked' => 'boolean'];
    }

    /** Рассчитывает заполненность восьми содержательных полей. */
    public function completion(): int
    {
        $fields = ['name', 'last_name', 'phone', 'city', 'birth_date', 'situation', 'strategy', 'email_verified_at'];

        return (int) round(collect($fields)->filter(fn ($field) => filled($this->$field))->count() / count($fields) * 100);
    }
}
