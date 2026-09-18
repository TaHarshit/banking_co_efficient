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
        if (Schema::hasTable('businesses') && !Schema::hasColumn('businesses', 'business_policies_message')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->text('business_policies_message')->nullable()->after('address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('businesses') && Schema::hasColumn('businesses', 'business_policies_message')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('business_policies_message');
            });
        }
    }
};
