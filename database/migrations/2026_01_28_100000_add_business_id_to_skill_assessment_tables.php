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
        // Add business_id to skill_assessment_sections
        if (Schema::hasTable('skill_assessment_sections') && !Schema::hasColumn('skill_assessment_sections', 'business_id')) {
            Schema::table('skill_assessment_sections', function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
                $table->index('business_id', 'sa_sections_business_idx');
            });
        }

        // Add business_id to skill_assessment_questions
        if (Schema::hasTable('skill_assessment_questions') && !Schema::hasColumn('skill_assessment_questions', 'business_id')) {
            Schema::table('skill_assessment_questions', function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->after('skill_assessment_section_id');
                $table->index('business_id', 'sa_questions_business_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('skill_assessment_sections') && Schema::hasColumn('skill_assessment_sections', 'business_id')) {
            Schema::table('skill_assessment_sections', function (Blueprint $table) {
                $table->dropIndex('sa_sections_business_idx');
                $table->dropColumn('business_id');
            });
        }

        if (Schema::hasTable('skill_assessment_questions') && Schema::hasColumn('skill_assessment_questions', 'business_id')) {
            Schema::table('skill_assessment_questions', function (Blueprint $table) {
                $table->dropIndex('sa_questions_business_idx');
                $table->dropColumn('business_id');
            });
        }
    }
};
