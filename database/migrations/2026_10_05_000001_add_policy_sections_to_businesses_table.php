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
        if (Schema::hasTable('businesses') && !Schema::hasColumn('businesses', 'policy_sections')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->json('policy_sections')->nullable()->after('business_policies_message');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('businesses') && Schema::hasColumn('businesses', 'policy_sections')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('policy_sections');
            });
        }
    }
};
