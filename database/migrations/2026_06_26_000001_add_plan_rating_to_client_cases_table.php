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
        Schema::table('client_cases', function (Blueprint $table) {
            $table->tinyInteger('plan_rating')->unsigned()->nullable()->after('action_plan')->comment('Rating 1 to 5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_cases', function (Blueprint $table) {
            $table->dropColumn('plan_rating');
        });
    }
};
