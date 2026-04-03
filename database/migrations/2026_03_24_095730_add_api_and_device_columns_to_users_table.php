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
            $table->string('api_token')->nullable()->after('password');
            $table->string('device_token')->nullable()->after('api_token');
            $table->string('platform')->nullable()->after('device_token');
            $table->date('dob')->nullable()->after('platform');
            $table->string('country_code')->nullable()->after('dob');
            $table->string('apple_id')->nullable()->after('country_code');
            $table->string('google_id')->nullable()->after('apple_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_token', 'device_token', 'platform', 'dob', 'country_code', 'apple_id', 'google_id']);
        });
    }
};
