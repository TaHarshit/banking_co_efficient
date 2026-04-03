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
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'title_fr')) {
                $table->string('title_fr')->nullable()->after('title');
            }
            if (!Schema::hasColumn('skill_assessment_exam_templates', 'description_fr')) {
                $table->text('description_fr')->nullable()->after('description');
            }
        });

        Schema::table('skill_assessment_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('skill_assessment_sections', 'title_fr')) {
                $table->string('title_fr')->nullable()->after('title');
            }
            if (!Schema::hasColumn('skill_assessment_sections', 'description_fr')) {
                $table->text('description_fr')->nullable()->after('description');
            }
        });

        Schema::table('skill_assessment_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('skill_assessment_questions', 'question_text_fr')) {
                $table->string('question_text_fr')->nullable()->after('question_text');
            }
            if (!Schema::hasColumn('skill_assessment_questions', 'helper_text_fr')) {
                $table->string('helper_text_fr')->nullable()->after('helper_text');
            }
        });

        Schema::table('skill_assessment_question_options', function (Blueprint $table) {
            if (!Schema::hasColumn('skill_assessment_question_options', 'option_text_fr')) {
                $table->string('option_text_fr')->nullable()->after('option_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_exam_templates', function (Blueprint $table) {
            $table->dropColumn(['title_fr', 'description_fr']);
        });

        Schema::table('skill_assessment_sections', function (Blueprint $table) {
            $table->dropColumn(['title_fr', 'description_fr']);
        });

        Schema::table('skill_assessment_questions', function (Blueprint $table) {
            $table->dropColumn(['question_text_fr', 'helper_text_fr']);
        });

        Schema::table('skill_assessment_question_options', function (Blueprint $table) {
            $table->dropColumn(['option_text_fr']);
        });
    }
};
