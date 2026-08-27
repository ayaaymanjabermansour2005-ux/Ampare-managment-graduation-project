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
        Schema::create('owner_applications', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();

            $table->string('password');

            $table->text('notes')->nullable();

            $table->string('generator_name')->nullable();
            $table->decimal('generator_price_per_kw', 10, 2)->nullable();
            $table->string('generator_currency', 3)->default('ILS');
            $table->unsignedInteger('generator_capacity_kw')->nullable();

            $table->string('generator_city', 100)->nullable();
            $table->foreignId('generator_neighborhood_id')
                ->nullable()
                ->constrained('neighborhoods')
                ->nullOnDelete();
            $table->string('generator_address')->nullable();
            $table->decimal('generator_latitude', 10, 7)->nullable();
            $table->decimal('generator_longitude', 10, 7)->nullable();

            $table->string('status', 20)->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->text('internal_note')->nullable();

            $table->foreignId('created_user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        DB::statement("
            ALTER TABLE owner_applications
            ADD CONSTRAINT chk_owner_applications_status CHECK (
                status IN ('pending', 'approved', 'rejected')
            )
        ");

        DB::statement("
            ALTER TABLE owner_applications
            ADD COLUMN email_pending_guard VARCHAR(255)
            GENERATED ALWAYS AS (CASE WHEN status = 'pending' THEN email ELSE NULL END) STORED
        ");
        DB::statement('ALTER TABLE owner_applications ADD UNIQUE INDEX uq_owner_applications_email_pending (email_pending_guard)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_applications');
    }
};
