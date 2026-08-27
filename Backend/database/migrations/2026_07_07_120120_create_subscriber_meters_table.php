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
        Schema::create('subscriber_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->restrictOnDelete();
            $table->string('meter_number', 50);
            $table->string('property_label', 150)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subscriber_id', 'status']);
        });

        DB::statement("
            ALTER TABLE subscriber_meters
            ADD COLUMN meter_number_active_guard VARCHAR(50)
            GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN meter_number ELSE NULL END) STORED
        ");
        DB::statement('ALTER TABLE subscriber_meters ADD UNIQUE INDEX uq_subscriber_meters_number_active_guard (meter_number_active_guard)');

        DB::statement("
            ALTER TABLE subscriber_meters
            ADD CONSTRAINT chk_subscriber_meters_status CHECK (
                status IN ('active', 'inactive')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriber_meters');
    }
};
