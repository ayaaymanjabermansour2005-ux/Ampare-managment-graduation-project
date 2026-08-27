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
        Schema::create('generators', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('name_en')->nullable();

            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->decimal('price_per_kw', 10, 2);

            $table->string('currency', 3)->default('ILS');

            $table->unsignedInteger('capacity_kw')->nullable();
            $table->unsignedTinyInteger('lines_count')->default(1);
            $table->string('fuel_type', 20)->default('diesel');

            $table->decimal('tank_capacity_liters', 8, 2)->nullable();

            $table->unsignedSmallInteger('rated_voltage')->nullable();
            $table->unsignedTinyInteger('rated_frequency_hz')->nullable();
            $table->unsignedTinyInteger('phase_count')->nullable();
            $table->unsignedInteger('rated_load_kw')->nullable();

            $table->unsignedInteger('service_interval_hours')->nullable();
            $table->date('next_service_due_at')->nullable();
            $table->date('installed_at')->nullable();

            $table->string('operating_schedule', 20)->default('24h');
            $table->time('operating_start_time')->nullable();
            $table->time('operating_end_time')->nullable();
            $table->string('status', 20)->default('pending_verification');
            $table->timestamp('last_low_fuel_alert_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
        });

        DB::statement("
            ALTER TABLE generators
            ADD CONSTRAINT chk_generators_status CHECK (
                status IN ('active', 'inactive', 'maintenance', 'pending_verification', 'rejected')
            )
        ");
        DB::statement("
            ALTER TABLE generators
            ADD CONSTRAINT chk_generators_operating_schedule CHECK (
                operating_schedule IN ('day', 'night', '24h', 'custom')
            )
        ");
        DB::statement("
            ALTER TABLE generators
            ADD CONSTRAINT chk_generators_currency CHECK (
                currency IN ('ILS', 'USD')
            )
        ");
        DB::statement("
            ALTER TABLE generators
            ADD CONSTRAINT chk_generators_fuel_type CHECK (
                fuel_type IN ('diesel', 'gas', 'petrol', 'dual')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generators');
    }
};
