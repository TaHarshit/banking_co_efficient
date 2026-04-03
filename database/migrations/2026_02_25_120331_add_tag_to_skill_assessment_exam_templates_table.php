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
            $table->string('tag')->nullable()->after('passing_percentage');
            $table->string('tag_fr')->nullable()->after('tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_exam_templates', function (Blueprint $table) {
            $table->dropColumn(['tag', 'tag_fr']);
        });
    }
};
