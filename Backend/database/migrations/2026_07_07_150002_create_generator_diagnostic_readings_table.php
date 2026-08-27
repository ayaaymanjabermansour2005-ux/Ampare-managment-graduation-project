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
        Schema::create('generator_diagnostic_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();

            $table->decimal('operating_hours', 10, 2);
            $table->decimal('temperature_celsius', 6, 2)->nullable();
            $table->decimal('oil_level_percent', 5, 2)->nullable();
            $table->decimal('load_percent', 5, 2)->nullable();
            $table->decimal('voltage', 6, 2)->nullable();
            $table->decimal('frequency_hz', 5, 2)->nullable();
            $table->string('smoke_level', 20)->default('none');
            $table->string('vibration_level', 20)->default('normal');
            $table->text('notes')->nullable();
            $table->date('reading_date');

            $table->timestamps();

            $table->index(['generator_id', 'reading_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generator_diagnostic_readings');
    }
};
