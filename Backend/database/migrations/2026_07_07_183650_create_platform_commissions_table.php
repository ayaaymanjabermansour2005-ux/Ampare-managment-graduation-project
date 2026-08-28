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
        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['owner_id', 'status']);
        });

        DB::statement("
            ALTER TABLE platform_commissions
            ADD CONSTRAINT chk_platform_commissions_status CHECK (
                status IN ('pending', 'earned', 'paid')
            )
        ");

        DB::statement('
            ALTER TABLE platform_commissions
            ADD CONSTRAINT chk_platform_commissions_rate CHECK (
                commission_rate >= 0 AND commission_rate <= 100
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
    }
};
