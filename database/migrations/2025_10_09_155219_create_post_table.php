<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
            $table->foreignId('category_id')
                // если пост может быть без категории
                ->nullable()
                ->constrained('category', 'id')
                // или 'cascade', если хотите удалять посты при удалении категории
                ->onDelete('set null');
                
            // Добавляем поле для текста предпросмотра (максимум 1000 символов)
            $table->string('preview_text', 1000)->nullable();

            // Добавляем поле для ссылки на изображение предпросмотра (максимум 500 символов)
            $table->string('preview_image', 500)->nullable();

            // счетчик просмотров
            $table->integer('views_count')->default(0);
            $table->string('slug')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post');
    }
};
