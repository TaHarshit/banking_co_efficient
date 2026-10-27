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
        if (Schema::hasTable('businesses')) {
            Schema::table('businesses', function (Blueprint $table) {
                if (!Schema::hasColumn('businesses', 'privacy_policy')) {
                    $table->longText('privacy_policy')->nullable()->after('policy_sections');
                }
                if (!Schema::hasColumn('businesses', 'privacy_policy_fr')) {
                    $table->longText('privacy_policy_fr')->nullable()->after('privacy_policy');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('businesses')) {
            Schema::table('businesses', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('businesses', 'privacy_policy')) {
                    $columns[] = 'privacy_policy';
                }
                if (Schema::hasColumn('businesses', 'privacy_policy_fr')) {
                    $columns[] = 'privacy_policy_fr';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
