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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->enum('question_type', ['single_select', 'multi_select', 'rating_scale', 'text_input']);
            $table->string('question_text_en');
            $table->string('question_text_fr');
            $table->string('helper_text_en')->nullable();
            $table->string('helper_text_fr')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
