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
        Schema::create('post_reaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('post','id')->onDelete('cascade'); // Связь с постом
            $table->foreignId('reaction_element_id')->constrained('reaction_element','id')->onDelete('cascade'); // ID стикера или гифки
            $table->string('ip_address'); // IP-адрес (опциональное поле)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reaction');
    }
};
