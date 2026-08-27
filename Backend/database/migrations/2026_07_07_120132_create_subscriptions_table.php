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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_meter_id')->constrained()->restrictOnDelete();
            $table->foreignId('generator_id')->constrained()->restrictOnDelete();
            $table->decimal('agreed_price_per_kw', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->decimal('requested_capacity_kw', 8, 2)->nullable();
            $table->string('schedule', 20)->default('24h');
            $table->string('billing_cycle', 20)->default('monthly');
            $table->time('service_start_time')->nullable();
            $table->time('service_end_time')->nullable();
            $table->string('contract_type', 50)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['generator_id', 'status']);
            $table->index(['subscriber_meter_id', 'status']);
        });

        DB::statement("
            ALTER TABLE subscriptions
            ADD COLUMN duplicate_guard_key VARCHAR(150)
            GENERATED ALWAYS AS (
                CASE WHEN status IN ('pending', 'active')
                THEN CONCAT(
                    subscriber_meter_id, '-',
                    generator_id, '-',
                    schedule, '-',
                    IFNULL(service_start_time, '00:00:00'), '-',
                    IFNULL(service_end_time, '00:00:00')
                )
                ELSE NULL END
            ) STORED
        ");
        DB::statement('ALTER TABLE subscriptions ADD UNIQUE INDEX uq_subscriptions_duplicate_guard (duplicate_guard_key)');

        DB::statement("
            ALTER TABLE subscriptions
            ADD CONSTRAINT chk_subscriptions_status CHECK (
                status IN ('pending', 'active', 'suspended', 'cancelled', 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
