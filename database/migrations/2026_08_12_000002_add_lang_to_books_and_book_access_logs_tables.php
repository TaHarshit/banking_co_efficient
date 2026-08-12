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
        if (Schema::hasTable('books') && !Schema::hasColumn('books', 'lang')) {
            Schema::table('books', function (Blueprint $table) {
                $table->string('lang', 10)->default('en')->after('title')->index();
            });
        }

        if (Schema::hasTable('book_access_logs') && !Schema::hasColumn('book_access_logs', 'lang')) {
            Schema::table('book_access_logs', function (Blueprint $table) {
                $table->string('lang', 10)->default('en')->after('access_token')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('books') && Schema::hasColumn('books', 'lang')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('lang');
            });
        }

        if (Schema::hasTable('book_access_logs') && Schema::hasColumn('book_access_logs', 'lang')) {
            Schema::table('book_access_logs', function (Blueprint $table) {
                $table->dropColumn('lang');
            });
        }
    }
};
