<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes optional user fields nullable since signup is simplified
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make all these optional fields nullable if they exist
            $columns = [
                'country_code',
                'phone_no',
                'gender',
                'height',
                'dob',
                'lat',
                'long',
                'city',
                'state',
                'address',
                'preference',
                'education',
                'school',
                'zodiac',
                'lang',
                'profession',
                'company',
                'looking_for',
                'description',
                'profile_image',
                'device_token',
                'platform',
                'api_token',
                'apple_id',
                'google_id'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - keeping them nullable is fine
    }
};
