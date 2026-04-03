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
        Schema::create('skill_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_assessment_section_id')->constrained('skill_assessment_sections')->onDelete('cascade');
            $table->enum('question_type', ['radio', 'multi_select', 'open_text']);
            $table->text('question_text');
            $table->text('helper_text')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_assessment_questions');
    }
};
