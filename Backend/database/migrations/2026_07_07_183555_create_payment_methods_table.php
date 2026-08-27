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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('type', 20);
            $table->boolean('is_default')->default(false);
            $table->string('currency', 3)->nullable();

            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->text('account_number')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE payment_methods
            ADD CONSTRAINT chk_payment_method_currency CHECK (
                (type = 'cash' AND currency IS NULL)
                OR
                (type IN ('bank', 'wallet') AND currency IS NOT NULL)
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE payment_methods
            ADD CONSTRAINT chk_payment_method_type CHECK (
                type IN ('bank', 'wallet', 'cash')
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
