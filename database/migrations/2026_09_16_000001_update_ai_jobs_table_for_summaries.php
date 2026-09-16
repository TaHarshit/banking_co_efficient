<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_jobs', 'client_id')) {
                $table->string('client_id')->nullable()->after('case_id');
            }
        });

        DB::statement("ALTER TABLE `ai_jobs` MODIFY `case_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `ai_jobs` MODIFY `job_type` ENUM('analyze_case', 'generate_plan', 'summarize_cases') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_jobs', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });

        DB::statement("ALTER TABLE `ai_jobs` MODIFY `job_type` ENUM('analyze_case', 'generate_plan') NOT NULL");
        DB::statement("ALTER TABLE `ai_jobs` MODIFY `case_id` BIGINT UNSIGNED NOT NULL");
    }
};
