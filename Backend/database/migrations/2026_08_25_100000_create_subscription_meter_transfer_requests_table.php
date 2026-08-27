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
        Schema::create('subscription_meter_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_subscriber_meter_id')
                ->constrained('subscriber_meters', indexName: 'smtr_from_meter_id_foreign')
                ->restrictOnDelete();
            $table->foreignId('to_subscriber_meter_id')
                ->constrained('subscriber_meters', indexName: 'smtr_to_meter_id_foreign')
                ->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subscription_id', 'status'], 'smtr_subscription_id_status_index');
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE subscription_meter_transfer_requests
            ADD CONSTRAINT chk_subscription_meter_transfer_requests_status CHECK (
                status IN ('pending', 'approved', 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_meter_transfer_requests');
    }
};
