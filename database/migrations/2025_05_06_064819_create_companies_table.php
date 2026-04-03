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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->text('address');
            $table->string('contact_no');
            $table->text('about_us');
            $table->string('company_logo')->nullable();
            $table->string('business_image_1')->nullable();
            $table->string('business_image_2')->nullable();
            $table->string('background_image')->nullable();
            $table->string('website_link')->nullable();
            $table->string('home_ar_img')->nullable();
            $table->string('van_ar_img')->nullable();
            $table->string('van_ar_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
