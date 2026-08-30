<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Run the migrations.
         */
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('key')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route');
            // IDEMPOTENCY-payload: يسمح للـ middleware باكتشاف إعادة استخدام
            // نفس المفتاح ببيانات مختلفة عن أول مرة، بدل الرد بصمت بنتيجة
            // الطلب الأول. Nullable لأن صفوفًا قديمة قد لا تملك القيمة.
            $table->string('payload_hash', 64)->nullable();
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
