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
        Schema::create('technician_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_task_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rated_by')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 500)->nullable();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE technician_ratings
            ADD CONSTRAINT chk_technician_ratings_rating_range
            CHECK (rating BETWEEN 1 AND 5)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_ratings');
    }
};
