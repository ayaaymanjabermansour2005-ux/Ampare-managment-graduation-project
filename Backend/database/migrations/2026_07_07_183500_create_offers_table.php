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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 10, 2);

            $table->string('target_mode', 20)->default('all');
            $table->string('beneficiary_type', 20)->nullable();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
        });

        DB::statement('ALTER TABLE offers ADD CONSTRAINT chk_offers_date_range CHECK (end_date > start_date)');

        DB::statement("
            ALTER TABLE offers
            ADD CONSTRAINT chk_offers_status CHECK (
                status IN ('active', 'cancelled')
            )
        ");
        DB::statement("
            ALTER TABLE offers
            ADD CONSTRAINT chk_offers_discount_type CHECK (
                discount_type IN ('percentage', 'fixed')
            )
        ");
        DB::statement("
            ALTER TABLE offers
            ADD CONSTRAINT chk_offers_target_mode CHECK (
                target_mode IN ('all', 'beneficiary', 'selected')
            )
        ");
        DB::statement("
            ALTER TABLE offers
            ADD CONSTRAINT chk_offers_beneficiary_type CHECK (
                beneficiary_type IS NULL OR beneficiary_type IN ('normal', 'special')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
