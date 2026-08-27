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
        Schema::create('article_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');

            $table->string('visitor_hash', 64);

            $table->timestamps();

            $table->unique(['article_id', 'visitor_hash']);
        });

        DB::statement('
            ALTER TABLE article_ratings
            ADD CONSTRAINT chk_article_ratings_rating CHECK (rating BETWEEN 1 AND 5)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_ratings');
    }
};
