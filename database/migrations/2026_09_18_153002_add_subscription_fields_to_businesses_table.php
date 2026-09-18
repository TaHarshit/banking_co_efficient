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
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('status')->constrained('plans')->nullOnDelete();
            $table->dateTime('subscription_start_date')->nullable()->after('plan_id');
            $table->dateTime('subscription_end_date')->nullable()->after('subscription_start_date');
            $table->integer('user_quota')->default(0)->after('subscription_end_date')->comment('Max employee seats allowed');
            $table->string('payment_mode', 50)->default('cash')->after('user_quota');
            $table->text('payment_notes')->nullable()->after('payment_mode')->comment('Cash receipt reference or remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'subscription_start_date',
                'subscription_end_date',
                'user_quota',
                'payment_mode',
                'payment_notes'
            ]);
        });
    }
};
