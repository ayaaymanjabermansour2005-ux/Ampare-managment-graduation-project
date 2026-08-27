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
        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('email')->nullable();
            $table->text('comment');

            $table->string('status', 20)->default('pending');

            $table->string('ip_address', 45)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->text('admin_reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['article_id', 'status']);
        });

        DB::statement("
            ALTER TABLE article_comments
            ADD CONSTRAINT chk_article_comments_status CHECK (
                status IN ('pending', 'approved', 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_comments');
    }
};
