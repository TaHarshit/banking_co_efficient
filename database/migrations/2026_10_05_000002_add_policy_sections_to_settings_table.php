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
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'business_policies_message')) {
                    $table->text('business_policies_message')->nullable()->after('feedback_form_link_fr');
                }
                if (!Schema::hasColumn('settings', 'policy_sections')) {
                    $table->json('policy_sections')->nullable()->after('business_policies_message');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'policy_sections')) {
                    $table->dropColumn('policy_sections');
                }
                if (Schema::hasColumn('settings', 'business_policies_message')) {
                    $table->dropColumn('business_policies_message');
                }
            });
        }
    }
};
