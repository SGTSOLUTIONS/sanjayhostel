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
        Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone');

    $table->foreignId('hostel_id')->constrained()->cascadeOnDelete();
    $table->foreignId('room_id')->nullable();
    $table->foreignId('bed_id')->nullable();

    $table->boolean('with_food')->default(false);
    $table->decimal('rent_amount', 10, 2);
    $table->string('addmissionform')->nullable();
    $table->string('image')->nullable();
    $table->string('aadharimage')->nullable();

    $table->date('join_date');
    $table->date('exit_date')->nullable();

    $table->enum('status', ['active', 'left'])->default('active');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
