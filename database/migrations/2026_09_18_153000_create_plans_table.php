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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('validity')->default(1)->comment('Validity duration in validity_type units');
            $table->string('validity_type', 20)->default('month')->comment('day, month, year');
            $table->tinyInteger('type')->default(0)->comment('0: Individual / In-App, 1: Business');
            $table->integer('user_quota')->nullable()->comment('Default employee/seat quota for business plans');
            $table->string('ios_product_id')->nullable();
            $table->string('android_product_id')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
