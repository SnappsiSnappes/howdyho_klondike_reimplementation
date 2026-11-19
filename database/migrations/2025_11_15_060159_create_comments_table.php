<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('username');           // Имя пользователя (анонимное)
            $table->text('text');                // Текст комментария (до 1000 символов)
            $table->timestamp('created_at')->useCurrent(); // Автоматически заполняется при создании
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate(); // Обновляется при изменении
            $table->ipAddress('ip_address');     // Явно указываем IP-адрес
            $table->boolean('approved')->default(false); // Модерация: одобрен или нет
            $table->foreignId('post_id')->constrained('post')->cascadeOnDelete(); // ← 'posts', а не 'post'

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
