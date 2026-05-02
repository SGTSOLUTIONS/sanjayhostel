<?php
// database/migrations/xxxx_create_room_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_ac')->default(false);
            $table->integer('sharing'); // Total beds in room
            $table->integer('normal_cot_count')->default(0); // Number of normal cots
            $table->integer('bunker_cot_count')->default(0); // Number of bunker cots
            $table->decimal('rent_with_food', 10, 2);
            $table->decimal('rent_without_food', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
