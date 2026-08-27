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
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generator_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('context_type', 20)->default('owner_diagnostic');
            $table->timestamps();

            $table->index(['user_id', 'generator_id']);
        });

        DB::statement("
            ALTER TABLE ai_chat_sessions
            ADD CONSTRAINT chk_ai_chat_sessions_context_type CHECK (
                context_type IN ('owner_diagnostic', 'subscriber_support')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
