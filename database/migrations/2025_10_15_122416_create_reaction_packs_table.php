<?php

// database/migrations/xxx_create_reaction_pack_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reaction_pack', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('folder_name');
            $table->text('preview_img');
            $table->timestamps();
            $table->unique(['name','folder_name', ]);

        });
    }

    public function down()
    {
        Schema::dropIfExists('reaction_pack');
    }
};