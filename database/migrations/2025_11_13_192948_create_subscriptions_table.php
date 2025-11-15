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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            
            // Payment gateway fields (for new installations)
            $table->string('gateway')->nullable()->after('subscription_plan_id'); // 'stripe' or 'razorpay'
            $table->string('gateway_customer_id')->nullable()->after('gateway');
            $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id');
            
            // Updated status enum
            $table->enum('status', ['trialing', 'active', 'canceled', 'past_due', 'expired', 'pending'])->default('pending')->after('gateway_subscription_id');
            
            // Subscription lifecycle dates
            $table->timestamp('trial_end_at')->nullable()->after('status');
            $table->timestamp('next_billing_at')->nullable()->after('trial_end_at');
            $table->timestamp('started_at')->nullable()->after('next_billing_at');
            $table->timestamp('canceled_at')->nullable()->after('started_at');
            
            // Metadata for storing additional gateway information
            $table->json('metadata')->nullable()->after('canceled_at');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
