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
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['owner_id', 'status']);
        });

        DB::statement("
            ALTER TABLE technicians
            ADD COLUMN user_id_active_guard BIGINT UNSIGNED
            GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN user_id ELSE NULL END) STORED
        ");
        DB::statement('ALTER TABLE technicians ADD UNIQUE INDEX uq_technicians_user_id_active_guard (user_id_active_guard)');

        DB::statement("
            ALTER TABLE technicians
            ADD CONSTRAINT chk_technicians_status CHECK (
                status IN ('active', 'inactive', 'suspended')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
