<?php
// database/migrations/2026_05_02_094007_create_staff_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('status', ['present', 'leave', 'half_day', 'holiday'])->default('present');
            $table->text('leave_reason')->nullable();
            $table->string('proof_image')->nullable(); // proof stored in public/hostelid/proof/
            $table->text('work_details')->nullable(); // ✅ Fixed: Changed $tabletext to $table->text
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['staff_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
