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
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'privacy_policy')) {
                $table->text('privacy_policy')->nullable();
            }
            if (!Schema::hasColumn('settings', 'privacy_policy_fr')) {
                $table->text('privacy_policy_fr')->nullable();
            }
            if (!Schema::hasColumn('settings', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable();
            }
            if (!Schema::hasColumn('settings', 'terms_and_conditions_fr')) {
                $table->text('terms_and_conditions_fr')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
