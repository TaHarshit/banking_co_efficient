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
        Schema::table('client_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('client_cases', 'client_summary')) {
                $table->json('client_summary')->nullable()->after('action_plan');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'ai_summary')) {
                $table->json('ai_summary')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('clients', 'summary_updated_at')) {
                $table->timestamp('summary_updated_at')->nullable()->after('ai_summary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_cases', function (Blueprint $table) {
            if (Schema::hasColumn('client_cases', 'client_summary')) {
                $table->dropColumn('client_summary');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'summary_updated_at')) {
                $table->dropColumn('summary_updated_at');
            }
            if (Schema::hasColumn('clients', 'ai_summary')) {
                $table->dropColumn('ai_summary');
            }
        });
    }
};
