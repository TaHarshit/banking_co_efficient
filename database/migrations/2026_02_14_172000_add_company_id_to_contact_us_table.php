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
            // Adding business_id instead of company_id to align with Business Portal
            $table->unsignedBigInteger('business_id')->nullable()->after('email');
            $table->index('business_id');
            // Optional: Foreign key if you want strict integrity
            // $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->dropColumn('business_id');
        });
    }
};
