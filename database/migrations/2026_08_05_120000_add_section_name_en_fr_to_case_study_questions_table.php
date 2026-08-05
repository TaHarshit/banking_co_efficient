<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('case_study_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('case_study_questions', 'section_name_en')) {
                $table->string('section_name_en')->nullable()->after('section_name');
            }
            if (!Schema::hasColumn('case_study_questions', 'section_name_fr')) {
                $table->string('section_name_fr')->nullable()->after('section_name_en');
            }
        });

        // Copy section_name data into section_name_en and section_name_fr if section_name exists
        if (Schema::hasColumn('case_study_questions', 'section_name')) {
            DB::statement("UPDATE case_study_questions SET section_name_en = section_name WHERE section_name_en IS NULL OR section_name_en = ''");
            DB::statement("UPDATE case_study_questions SET section_name_fr = section_name WHERE section_name_fr IS NULL OR section_name_fr = ''");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_study_questions', function (Blueprint $table) {
            if (Schema::hasColumn('case_study_questions', 'section_name_en')) {
                $table->dropColumn(['section_name_en', 'section_name_fr']);
            }
        });
    }
};
