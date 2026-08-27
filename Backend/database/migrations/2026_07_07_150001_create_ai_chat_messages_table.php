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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE ai_chat_messages
            ADD CONSTRAINT chk_ai_chat_messages_role CHECK (
                role IN ('user', 'assistant')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
