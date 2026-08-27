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
        Schema::create('payment_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE payment_reviews
            ADD CONSTRAINT chk_payment_reviews_status CHECK (
                status IN ('approved', 'needs_correction', 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reviews');
    }
};
