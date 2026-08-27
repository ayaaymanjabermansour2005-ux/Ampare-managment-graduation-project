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
        Schema::create('subscription_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();

            $table->string('request_type', 30);
            $table->string('event_type', 30)->nullable();
            $table->text('description');

            $table->decimal('extra_capacity_kw', 8, 2)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->decimal('fee_amount', 8, 2)->nullable();
            $table->string('fee_currency', 3)->nullable();

            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
            $table->index(['request_type', 'status']);
        });

        DB::statement('ALTER TABLE subscription_service_requests ADD CONSTRAINT chk_ssr_date_range CHECK (ends_at > starts_at)');

        DB::statement("
            ALTER TABLE subscription_service_requests
            ADD CONSTRAINT chk_ssr_status CHECK (
                status IN ('pending', 'approved', 'rejected', 'cancelled')
            )
        ");

        DB::statement("
            ALTER TABLE subscription_service_requests
            ADD CONSTRAINT chk_ssr_request_type CHECK (
                request_type IN ('event', 'extra_capacity', 'extra_hours', 'maintenance', 'medical_priority', 'other')
            )
        ");
        DB::statement("
            ALTER TABLE subscription_service_requests
            ADD CONSTRAINT chk_ssr_event_type CHECK (
                event_type IS NULL OR event_type IN ('wedding', 'exam', 'religious_event', 'family_event', 'other')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_service_requests');
    }
};
