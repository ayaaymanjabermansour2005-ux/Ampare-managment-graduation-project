<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->foreignId('generator_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing general admin conversations may not have a generator, so this
        // column intentionally remains nullable when rolling the migration back.
    }
};
