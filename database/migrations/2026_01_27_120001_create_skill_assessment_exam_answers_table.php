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
        Schema::create('skill_assessment_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('skill_assessment_exam_id');
            $table->foreign('skill_assessment_exam_id', 'sa_answers_exam_id_fk')
                ->references('id')
                ->on('skill_assessment_exams')
                ->onDelete('cascade');
            $table->unsignedBigInteger('skill_assessment_question_id');
            $table->foreign('skill_assessment_question_id', 'sa_answers_question_id_fk')
                ->references('id')
                ->on('skill_assessment_questions')
                ->onDelete('cascade');
            $table->text('text_answer')->nullable();
            $table->json('selected_option_ids')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();

            $table->index('skill_assessment_exam_id', 'sa_answers_exam_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_assessment_exam_answers');
    }
};
