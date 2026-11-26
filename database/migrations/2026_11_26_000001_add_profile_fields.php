<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Добавляет профиль, права и независимый журнал действий. */
return new class extends Migration
{
    /** Создаёт поля профиля и журнал. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('city', 120)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('situation')->nullable();
            $table->text('strategy')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_blocked')->default(false);
        });
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action');
            $table->timestamps();
        });
    }

    /** Удаляет созданную структуру. */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['last_name', 'phone', 'city', 'birth_date', 'situation', 'strategy', 'is_admin', 'is_blocked']));
    }
};
