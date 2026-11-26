<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Проверяет только редактируемые поля профиля. */
class UpdateProfileRequest extends FormRequest
{
    /** Разрешает изменение собственного профиля. */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Ограничивает длину текста и допустимые даты. */
    public function rules(): array
    {
        return ['name' => 'required|string|max:100', 'last_name' => 'nullable|string|max:100', 'phone' => ['nullable', 'regex:/^\\+?[0-9 ()-]{7,32}$/'], 'city' => 'nullable|string|max:120', 'birth_date' => 'nullable|date|before_or_equal:today|after:1900-01-01', 'situation' => 'nullable|string|max:5000', 'strategy' => 'nullable|string|max:5000'];
    }

    /** Русские подписи ошибок валидации. */
    public function attributes(): array
    {
        return ['name' => 'Имя', 'last_name' => 'Фамилия', 'phone' => 'Телефон', 'city' => 'Город', 'birth_date' => 'Дата рождения', 'situation' => 'Моя ситуация', 'strategy' => 'Моя инвестиционная стратегия'];
    }
}
