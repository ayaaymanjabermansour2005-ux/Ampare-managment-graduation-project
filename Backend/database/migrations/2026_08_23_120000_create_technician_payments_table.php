<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technician_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['owner_id', 'status']);
            $table->index(['technician_id', 'status']);
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE technician_payments
            ADD CONSTRAINT chk_technician_payments_status CHECK (
                status IN ('pending', 'approved', 'rejected')
            )
        ");

        DB::statement("
            ALTER TABLE technician_payments
            ADD CONSTRAINT chk_technician_payments_currency CHECK (
                currency IN ('ILS', 'USD')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_payments');
    }
};
