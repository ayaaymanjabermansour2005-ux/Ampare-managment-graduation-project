<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Run the migrations.
         */
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();

            $table->foreignId('meter_reading_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('service_request_id')
                ->nullable()
                ->unique()
                ->constrained('subscription_service_requests')
                ->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->foreignId('discount_id')
                ->nullable()
                ->constrained('offers')
                ->nullOnDelete();
            $table->decimal('final_amount', 10, 2);
            $table->string('currency', 3)->default('ILS');

            $table->decimal('exchange_rate', 10, 4)->nullable();

            $table->decimal('final_amount_ils', 10, 2);

            $table->date('due_date');

            $table->string('status', 30)
                ->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['subscription_id', 'status']);
            $table->index('due_date');

            $table->index(['status', 'due_date']);
        });

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT chk_invoices_exchange_rate CHECK (
                (currency = 'ILS' AND exchange_rate IS NULL)
                OR
                (currency != 'ILS' AND exchange_rate IS NOT NULL)
            )
        ");

        DB::statement("
            ALTER TABLE invoices
            ADD CONSTRAINT chk_invoices_status CHECK (
                status IN ('pending', 'partially_paid', 'paid', 'overdue', 'cancelled')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
