<?php

use Filament\Tables\Table;
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
        Schema::create('post_view', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('post')->cascadeOnDelete(); // ← 'posts', а не 'post'
            $table->ipAddress('ip_address'); // явно укажем имя, хотя по умолчанию и так ip_address
            $table->timestamps();

            // Уникальность: один IP — один просмотр на один пост
            $table->unique(['post_id', 'ip_address'], 'post_view_post_id_ip_address_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_view');
    }
};
