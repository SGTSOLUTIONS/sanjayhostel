<?php
// database/migrations/2024_01_01_000000_create_expenses_table.php

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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_name'); // Expense name/title
            $table->decimal('amount', 10, 2); // Amount spent
            $table->string('month'); // Month (format: Y-m)
            $table->date('expense_date'); // Date of expense
            $table->string('category')->nullable(); // Category (electricity, maintenance, salary, etc.)
            $table->text('note')->nullable(); // Additional notes/description
            $table->string('payment_method')->nullable(); // Cash, Bank Transfer, UPI, etc.
            $table->string('receipt')->nullable(); // Receipt file path
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // User who created
            $table->foreignId('hostel_id')->nullable()->constrained()->onDelete('set null'); // Which hostel (for admin filtering)
            $table->timestamps();

            // Indexes for faster queries
            $table->index('month');
            $table->index('category');
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
