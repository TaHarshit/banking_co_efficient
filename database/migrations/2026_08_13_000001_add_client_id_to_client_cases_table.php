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
            if (!Schema::hasColumn('client_cases', 'client_id')) {
                $table->string('client_id')->nullable()->after('user_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_cases', function (Blueprint $table) {
            if (Schema::hasColumn('client_cases', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });
    }
};
