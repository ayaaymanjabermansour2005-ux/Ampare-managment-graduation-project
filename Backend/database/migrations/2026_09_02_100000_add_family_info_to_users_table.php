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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('family_members_count')->nullable()->after('instagram_url');
            $table->boolean('has_sick_family_member')->default(false)->after('family_members_count');
            $table->text('sick_family_member_illness')->nullable()->after('has_sick_family_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['family_members_count', 'has_sick_family_member', 'sick_family_member_illness']);
        });
    }
};
