<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user1_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('user2_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('user1_deleted_at')->nullable();
            $table->timestamp('user2_deleted_at')->nullable();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE conversations
            ADD COLUMN participant_low_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (LEAST(user1_id, user2_id)) STORED
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE conversations
            ADD COLUMN participant_high_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (GREATEST(user1_id, user2_id)) STORED
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE conversations
            ADD CONSTRAINT uq_conversations_participants
            UNIQUE (participant_low_id, participant_high_id)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
