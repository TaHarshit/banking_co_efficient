<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create a default exam template for existing sections
        $existingSections = DB::table('skill_assessment_sections')->count();

        if ($existingSections > 0) {
            DB::table('skill_assessment_exam_templates')->insert([
                'title' => 'Default Exam',
                'description' => 'Auto-created exam template for existing sections',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add the FK column to sections
        Schema::table('skill_assessment_sections', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_assessment_exam_template_id')->nullable()->after('id');
            $table->foreign('skill_assessment_exam_template_id', 'sa_sections_exam_template_fk')
                ->references('id')
                ->on('skill_assessment_exam_templates')
                ->onDelete('cascade');
        });

        // Assign existing sections to the default exam template
        if ($existingSections > 0) {
            $defaultExamId = DB::table('skill_assessment_exam_templates')
                ->where('title', 'Default Exam')
                ->whereNull('business_id')
                ->value('id');

            if ($defaultExamId) {
                DB::table('skill_assessment_sections')
                    ->whereNull('skill_assessment_exam_template_id')
                    ->whereNull('business_id')
                    ->update(['skill_assessment_exam_template_id' => $defaultExamId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_sections', function (Blueprint $table) {
            $table->dropForeign('sa_sections_exam_template_fk');
            $table->dropColumn('skill_assessment_exam_template_id');
        });

        // Remove default exam template
        DB::table('skill_assessment_exam_templates')
            ->where('title', 'Default Exam')
            ->whereNull('business_id')
            ->delete();
    }
};
