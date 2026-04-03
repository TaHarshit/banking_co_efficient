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
        Schema::table('skill_assessment_exam_templates', function (Blueprint $table) {
            // Add exam level fields if they don't exist
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'exam_level')) {
                $table->string('exam_level')->nullable()->after('passing_percentage');
            }
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'exam_level_fr')) {
                $table->string('exam_level_fr')->nullable()->after('exam_level');
            }
            
            // Add tags fields if they don't exist
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'tags')) {
                $table->json('tags')->nullable()->after('exam_level_fr');
            }
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'tags_fr')) {
                $table->json('tags_fr')->nullable()->after('tags');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_exam_templates', function (Blueprint $table) {
            // Remove exam level fields
            if (Schema::hasColumn('skill_assessment_exam_templates', 'exam_level')) {
                $table->dropColumn('exam_level');
            }
            if (Schema::hasColumn('skill_assessment_exam_templates', 'exam_level_fr')) {
                $table->dropColumn('exam_level_fr');
            }
            
            // Remove tags fields
            if (Schema::hasColumn('skill_assessment_exam_templates', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('skill_assessment_exam_templates', 'tags_fr')) {
                $table->dropColumn('tags_fr');
            }
        });
    }
};
