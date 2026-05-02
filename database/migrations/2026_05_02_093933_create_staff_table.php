<?php
// database/migrations/2026_05_02_094006_create_staff_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('position'); // cleaner, cook, security, manager, etc.
            $table->decimal('salary', 10, 2);
            $table->date('joining_date');
            $table->text('address')->nullable();
            $table->string('aadhar_number')->nullable();
            $table->string('profile_image')->nullable();
            $table->foreignId('hostel_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'left'])->default('active');
            $table->timestamps();

            $table->index('hostel_id');
            $table->index('position');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
