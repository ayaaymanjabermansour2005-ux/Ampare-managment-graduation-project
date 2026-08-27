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
        Schema::create('commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_generators_count');
            $table->unsignedInteger('max_generators_count')->nullable();
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('min_generators_count');
            $table->index('is_active');
        });

        DB::statement('
            ALTER TABLE commission_tiers
            ADD CONSTRAINT chk_commission_tiers_range CHECK (
                max_generators_count IS NULL OR max_generators_count >= min_generators_count
            )
        ');

        DB::statement('
            ALTER TABLE commission_tiers
            ADD CONSTRAINT chk_commission_tiers_rate CHECK (
                commission_rate >= 0 AND commission_rate <= 100
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_tiers');
    }
};
