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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->nullableMorphs('complainable');

            $table->foreignId('conversation_id')
                ->nullable()
                ->unique()
                ->constrained('conversations')
                ->nullOnDelete();

            $table->string('subject');
            $table->text('description');
            $table->string('status', 20)->default('pending');

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['submitted_by', 'status']);
        });

        DB::statement("
            ALTER TABLE complaints
            ADD CONSTRAINT chk_complaints_status CHECK (
                status IN ('pending', 'in_progress', 'resolved')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
