<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generator_health_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('risk_level', 20);
            $table->text('summary');
            $table->text('recommendation')->nullable();
            $table->json('input_snapshot')->nullable();
            $table->timestamps();

            $table->index(['generator_id', 'period_start']);

            $table->unique(
                ['generator_id', 'period_start', 'period_end'],
                'uq_generator_health_reports_period'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generator_health_reports');
    }
};
