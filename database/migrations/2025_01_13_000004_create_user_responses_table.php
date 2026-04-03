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
        Schema::create('user_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->enum('response_type', ['option', 'text', 'rating']);
            $table->foreignId('option_id')->nullable()->constrained('question_options')->onDelete('set null');
            $table->text('response_value')->nullable();
            $table->integer('rating_value')->nullable();
            $table->timestamps();

            // Index for faster lookups
            $table->index(['user_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_responses');
    }
};
