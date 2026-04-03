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
        Schema::table('contact_us', function (Blueprint $table) {
            if (Schema::hasColumn('contact_us', 'company_id')) {
                $table->renameColumn('company_id', 'business_id');
            } else {
                if (!Schema::hasColumn('contact_us', 'business_id')) {
                    $table->unsignedBigInteger('business_id')->nullable()->after('email');
                    $table->index('business_id');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            if (Schema::hasColumn('contact_us', 'business_id')) {
                $table->renameColumn('business_id', 'company_id');
            }
        });
    }
};
