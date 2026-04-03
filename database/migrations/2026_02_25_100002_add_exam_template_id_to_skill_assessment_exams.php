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
        Schema::table('skill_assessment_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_assessment_exam_template_id')->nullable()->after('user_id');
            $table->foreign('skill_assessment_exam_template_id', 'sa_exams_template_id_fk')
                ->references('id')
                ->on('skill_assessment_exam_templates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_exams', function (Blueprint $table) {
            $table->dropForeign('sa_exams_template_id_fk');
            $table->dropColumn('skill_assessment_exam_template_id');
        });
    }
};
