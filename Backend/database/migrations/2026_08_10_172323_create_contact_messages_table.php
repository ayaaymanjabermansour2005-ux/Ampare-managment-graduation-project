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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('subject', 30)->default('general');
            $table->text('message');

            $table->string('status', 20)->default('new');
            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        DB::statement("
            ALTER TABLE contact_messages
            ADD CONSTRAINT chk_contact_messages_subject CHECK (
                subject IN ('general', 'owner', 'technical', 'partnership')
            )
        ");
        DB::statement("
            ALTER TABLE contact_messages
            ADD CONSTRAINT chk_contact_messages_status CHECK (
                status IN ('new', 'in_progress', 'resolved')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
