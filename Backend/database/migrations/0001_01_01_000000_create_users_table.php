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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('status', 20)
                ->default('active');

            $table->boolean('is_guest_demo')->default(false);

            $table->foreignId('plan_id')->nullable();

            $table->string('commission_mode', 10)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();

            $table->unsignedTinyInteger('failed_login_attempts')
                ->default(0);

            $table->unsignedTinyInteger('lockout_count')
                ->default(0);

            $table->timestamp('locked_until')->nullable();

            $table->timestamp('last_locked_at')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->string('avatar_path')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('bio')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->unsignedSmallInteger('family_members_count')->nullable();
            $table->boolean('has_sick_family_member')->default(false);
            $table->text('sick_family_member_illness')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_guest_demo');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->index();
            $table->string('ip_address', 45)
                ->nullable();
            $table->text('user_agent')
                ->nullable();
            $table->longText('payload');
            $table->integer('last_activity')
                ->index();
        });

        DB::statement('
            ALTER TABLE users
            ADD COLUMN email_active_guard VARCHAR(255)
            GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN email ELSE NULL END) STORED
        ');
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX uq_users_email_active_guard (email_active_guard)');

        DB::statement('
            ALTER TABLE users
            ADD COLUMN phone_active_guard VARCHAR(255)
            GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN phone ELSE NULL END) STORED
        ');
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX uq_users_phone_active_guard (phone_active_guard)');

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_status CHECK (
                status IN ('active', 'inactive', 'suspended', 'pending_review')
            )
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_commission_mode CHECK (
                commission_mode IS NULL OR commission_mode IN ('fixed', 'tiered')
            )
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_commission_rate_consistency CHECK (
                (commission_mode = 'fixed' AND commission_rate IS NOT NULL)
                OR
                (commission_mode = 'tiered' AND commission_rate IS NULL)
                OR
                commission_mode IS NULL
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
