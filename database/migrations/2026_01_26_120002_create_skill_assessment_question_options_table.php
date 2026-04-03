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
        Schema::create('skill_assessment_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('skill_assessment_question_id');
            $table->foreign('skill_assessment_question_id', 'sa_question_options_question_id_fk')
                  ->references('id')
                  ->on('skill_assessment_questions')
                  ->onDelete('cascade');
            $table->string('option_text');
            $table->decimal('weightage', 5, 2)->default(0.00)->comment('Weightage for multi-select questions');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_assessment_question_options');
    }
};
