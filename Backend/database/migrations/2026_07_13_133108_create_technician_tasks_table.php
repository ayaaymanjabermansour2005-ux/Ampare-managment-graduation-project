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
        Schema::create('technician_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reviewer_role', 20)->nullable();
            $table->text('admin_override_reason')->nullable();
            $table->nullableMorphs('taskable');
            $table->string('type', 40);
            $table->string('status', 30)->default('pending');
            $table->text('instructions')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['generator_id', 'status']);
            $table->index(['technician_id', 'status']);
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE technician_tasks
            ADD CONSTRAINT chk_technician_tasks_status CHECK (
                status IN ('pending', 'assigned', 'on_the_way', 'in_progress', 'waiting_parts', 'submitted', 'approved', 'rejected', 'cancelled')
            )
        ");
        DB::statement("
            ALTER TABLE technician_tasks
            ADD CONSTRAINT chk_technician_tasks_type CHECK (
                type IN ('new_subscription_installation', 'meter_reading', 'wiring_maintenance', 'fault_repair', 'general_maintenance')
            )
        ");
        DB::statement("
            ALTER TABLE technician_tasks
            ADD CONSTRAINT chk_technician_tasks_reviewer_role CHECK (
                reviewer_role IS NULL OR reviewer_role IN ('owner', 'admin')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_tasks');
    }
};
