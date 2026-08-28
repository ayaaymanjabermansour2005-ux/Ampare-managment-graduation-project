<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();

            $table->date('reading_date');
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('current_reading', 10, 2);

            $table->decimal('consumed_kw', 10, 2)
                ->storedAs('current_reading - previous_reading');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 20)->default('pending_approval');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->unique(['subscription_id', 'reading_date']);

            $table->index(['subscription_id', 'status']);
        });

        DB::statement('ALTER TABLE meter_readings ADD CONSTRAINT chk_meter_readings_non_negative CHECK (current_reading >= previous_reading)');

        DB::statement("
            ALTER TABLE meter_readings
            ADD CONSTRAINT chk_meter_readings_status CHECK (
                status IN ('pending_approval', 'approved', 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
