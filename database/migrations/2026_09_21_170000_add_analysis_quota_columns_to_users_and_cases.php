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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'free_analyses_used')) {
                $table->integer('free_analyses_used')->default(0)->after('status')->comment('Count of free lifetime analyses used (up to 3)');
            }
            if (!Schema::hasColumn('users', 'paid_analyses_credits')) {
                $table->integer('paid_analyses_credits')->default(0)->after('free_analyses_used')->comment('Consumable credits purchased via Single Analysis IAP');
            }
        });

        Schema::table('client_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('client_cases', 'can_export_pdf')) {
                $table->boolean('can_export_pdf')->default(false)->after('ai_analysis')->comment('True if analyzed with paid credit, pro, or business plan');
            }
            if (!Schema::hasColumn('client_cases', 'is_full_profile')) {
                $table->boolean('is_full_profile')->default(false)->after('can_export_pdf')->comment('True if analyzed with full behavioral profile');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['free_analyses_used', 'paid_analyses_credits']);
        });

        Schema::table('client_cases', function (Blueprint $table) {
            $table->dropColumn(['can_export_pdf', 'is_full_profile']);
        });
    }
};
