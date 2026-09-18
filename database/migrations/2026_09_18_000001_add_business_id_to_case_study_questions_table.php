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
        if (Schema::hasTable('case_study_questions') && !Schema::hasColumn('case_study_questions', 'business_id')) {
            Schema::table('case_study_questions', function (Blueprint $table) {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('businesses')
                    ->onDelete('cascade');
                $table->index('business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('case_study_questions') && Schema::hasColumn('case_study_questions', 'business_id')) {
            Schema::table('case_study_questions', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }
};
