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
        Schema::create('skill_assessment_exam_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Optional time limit in minutes');
            $table->decimal('passing_percentage', 5, 2)->nullable()->comment('Optional passing percentage');
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
        Schema::dropIfExists('skill_assessment_exam_templates');
    }
};
