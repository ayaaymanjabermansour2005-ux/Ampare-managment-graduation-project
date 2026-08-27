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
        Schema::create('subscription_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_request_id')
                ->unique()
                ->constrained('subscription_service_requests')
                ->restrictOnDelete();

            $table->decimal('extra_capacity_kw', 8, 2);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->timestamps();

            $table->index(['subscription_id', 'starts_at', 'ends_at']);
        });

        DB::statement('ALTER TABLE subscription_overrides ADD CONSTRAINT chk_subscription_overrides_range CHECK (ends_at > starts_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_overrides');
    }
};
