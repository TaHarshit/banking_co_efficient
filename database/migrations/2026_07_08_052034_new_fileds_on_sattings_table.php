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
                $table->text('privacy_policy_fr')->after('privacy_policy')->nullable();
                $table->text('terms_and_conditions_fr')->after('terms_and_conditions')->nullable();
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
