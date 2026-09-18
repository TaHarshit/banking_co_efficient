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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->text('receipt_id')->nullable();
            $table->text('purchase_token')->nullable();
            $table->string('purchase_from')->default('0')->comment('0: iOS, 1: Android, cash, stripe, etc.');
            $table->dateTime('subscription_start_date')->nullable();
            $table->dateTime('subscription_end_date')->nullable();
            $table->integer('user_quota')->default(0)->comment('Quota snapshot for business subscriptions');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_status', 50)->default('paid');
            $table->tinyInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->text('notes')->nullable()->comment('Remarks or cash receipt details');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
