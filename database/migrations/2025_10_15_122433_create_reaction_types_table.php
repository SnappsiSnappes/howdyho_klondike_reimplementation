<?php

// database/migrations/xxx_create_reaction_type_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reaction_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('sticker')->nullable();
            $table->timestamps();
            $table->unique(['name']);

        });
    }

    public function down()
    {
        Schema::dropIfExists('reaction_type');
    }
};