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
        Schema::create('generator_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('technicians')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['generator_id', 'technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generator_technician');
    }
};
