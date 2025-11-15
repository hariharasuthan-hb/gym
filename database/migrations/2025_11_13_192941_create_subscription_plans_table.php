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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->enum('duration_type', ['trial', 'daily', 'weekly', 'monthly', 'yearly']);
            $table->integer('duration'); // Number of days/weeks/months/years
            $table->decimal('price', 10, 2);
            $table->integer('trial_days')->default(0)->after('price');
            $table->string('stripe_price_id')->nullable()->after('trial_days');
            $table->string('razorpay_plan_id')->nullable()->after('stripe_price_id');
            $table->boolean('is_active')->default(true);
            $table->text('features')->nullable(); // JSON or text field for plan features
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
