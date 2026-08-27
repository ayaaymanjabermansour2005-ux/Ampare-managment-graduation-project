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
        Schema::create('fuel_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generator_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('liters', 8, 2);
            $table->decimal('cost_amount', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('cost_amount_ils', 10, 2);

            $table->date('purchased_at');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['generator_id', 'purchased_at']);
        });

        DB::statement("
            ALTER TABLE fuel_purchases
            ADD CONSTRAINT chk_fuel_purchases_exchange_rate CHECK (
                (currency = 'ILS' AND exchange_rate IS NULL)
                OR
                (currency != 'ILS' AND exchange_rate IS NOT NULL)
            )
        ");

        DB::statement("
            ALTER TABLE fuel_purchases
            ADD CONSTRAINT chk_fuel_purchases_currency CHECK (
                currency IN ('ILS', 'USD')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_purchases');
    }
};
