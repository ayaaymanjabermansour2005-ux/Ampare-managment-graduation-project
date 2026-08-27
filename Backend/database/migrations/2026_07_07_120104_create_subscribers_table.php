<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('neighborhood_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('address')->nullable();
            $table->timestamp('joined_at')->useCurrent();

            $table->string('beneficiary_type', 20)->default('normal');
            $table->foreignId('beneficiary_type_changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('beneficiary_type_changed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
