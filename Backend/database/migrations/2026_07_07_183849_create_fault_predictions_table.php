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
        Schema::create('fault_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->restrictOnDelete();

            $table->string('source', 30)->default('external_ml');
            $table->foreignId('ai_chat_session_id')
                ->nullable()
                ->unique()
                ->constrained('ai_chat_sessions')
                ->nullOnDelete();
            $table->foreignId('generator_diagnostic_reading_id')
                ->nullable()
                ->unique()
                ->constrained('generator_diagnostic_readings')
                ->nullOnDelete();

            $table->boolean('is_actual_fault')->nullable();
            $table->string('prediction_type')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('recommendation')->nullable();
            $table->json('input_snapshot')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['generator_id', 'status']);
        });

        DB::statement("
            ALTER TABLE fault_predictions
            ADD CONSTRAINT chk_fault_predictions_status CHECK (
                status IN ('pending', 'confirmed', 'dismissed')
            )
        ");
        DB::statement("
            ALTER TABLE fault_predictions
            ADD CONSTRAINT chk_fault_predictions_source CHECK (
                source IN ('external_ml', 'chat', 'sensor_analysis')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fault_predictions');
    }
};
