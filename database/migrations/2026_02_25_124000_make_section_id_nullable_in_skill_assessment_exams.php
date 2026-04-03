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
            $table->unsignedBigInteger('skill_assessment_section_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_assessment_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_assessment_section_id')->nullable(false)->change();
        });
    }
};
