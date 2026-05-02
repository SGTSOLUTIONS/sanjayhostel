<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'upi'])->default('cash')->after('amount');
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->string('receipt_number')->nullable()->after('transaction_id');
            $table->decimal('late_fee', 10, 2)->default(0)->after('receipt_number');
            $table->decimal('discount', 10, 2)->default(0)->after('late_fee');
            $table->text('notes')->nullable()->after('discount');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->onDelete('set null');
            $table->enum('status', ['paid', 'pending', 'overdue', 'partial'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'transaction_id',
                'receipt_number',
                'late_fee',
                'discount',
                'notes',
                'created_by'
            ]);
            $table->enum('status', ['paid', 'pending'])->default('pending')->change();
        });
    }
};
