<?php

// database/migrations/xxx_create_reaction_element_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reaction_element', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('file_name');
            $table->foreignId('reaction_pack_id')->constrained('reaction_pack')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('reaction_type')->onDelete('cascade');
            $table->timestamps();


            // Уникальный индекс для предотвращения дублирования реакций
            $table->unique(['file_name']);

        });
    }

    public function down()
    {
        Schema::dropIfExists('reaction_element');
    }
};