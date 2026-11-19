<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название категории
            $table->string('slug')->unique(); // Уникальный URL-идентификатор
            $table->timestamps();
            $table->foreignId('parent_id')->nullable()->constrained('category')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('preview_img')->nullable();
            $table->string('collection_img')->nullable();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};