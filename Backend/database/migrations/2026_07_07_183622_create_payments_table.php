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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('subscriber');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('amount_ils', 10, 2);
            $table->string('transaction_reference')->nullable()->unique();
            $table->text('note')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['invoice_id', 'status']);
            $table->index(['reviewed_by', 'status']);
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT chk_payments_exchange_rate CHECK (
                (currency = 'ILS' AND exchange_rate IS NULL)
                OR
                (currency != 'ILS' AND exchange_rate IS NOT NULL)
            )
        ");

        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT chk_payments_status CHECK (
                status IN ('pending', 'needs_correction', 'paid', 'rejected', 'cancelled')
            )
        ");

        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT chk_payments_source CHECK (
                source IN ('subscriber', 'adjustment', 'gateway')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
