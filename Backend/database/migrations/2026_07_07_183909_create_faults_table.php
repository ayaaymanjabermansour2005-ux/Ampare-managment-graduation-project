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
        Schema::create('faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->restrictOnDelete();

            $table->foreignId('fault_prediction_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('ai_chat_session_id')
                ->nullable()
                ->unique()
                ->constrained('ai_chat_sessions')
                ->nullOnDelete();

            $table->foreignId('conversation_id')
                ->nullable()
                ->unique()
                ->constrained('conversations')
                ->nullOnDelete();

            $table->foreignId('reported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source', 30)->default('manual');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->timestamp('reported_at')->useCurrent();

            $table->string('status', 30)->default('pending_verification');
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('repair_method', 30)->nullable();

            $table->timestamp('resolved_at')->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->text('admin_override_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['generator_id', 'status']);
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE faults
            ADD CONSTRAINT chk_faults_status CHECK (
                status IN ('pending_verification', 'verified', 'rejected', 'in_repair', 'resolved', 'closed')
            )
        ");
        DB::statement("
            ALTER TABLE faults
            ADD CONSTRAINT chk_faults_source CHECK (
                source IN ('subscriber_report', 'ai_prediction', 'manual')
            )
        ");
        DB::statement("
            ALTER TABLE faults
            ADD CONSTRAINT chk_faults_priority CHECK (
                priority IN ('low', 'medium', 'high', 'critical')
            )
        ");
        DB::statement("
            ALTER TABLE faults
            ADD CONSTRAINT chk_faults_repair_method CHECK (
                repair_method IS NULL OR repair_method IN ('owner_fixed', 'internal_technician')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faults');
    }
};
