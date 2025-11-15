<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\PaymentGateway\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {
        // Middleware is applied in routes/frontend.php
    }

    /**
     * Show subscription overview page.
     */
    public function index(): View
    {
        $user = auth()->user();
        
        $subscriptions = $user->subscriptions()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeSubscription = $subscriptions->firstWhere('status', 'active') 
            ?? $subscriptions->firstWhere('status', 'trialing');

        return view('frontend.member.subscription.index', [
            'subscriptions' => $subscriptions,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Subscription $subscription): RedirectResponse
    {
        $user = auth()->user();

        // Verify subscription belongs to user
        if ($subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Check if subscription can be canceled
        if ($subscription->isCanceled()) {
            return redirect()->route('member.subscription.index')
                ->with('info', 'This subscription is already canceled.');
        }

        try {
            $canceled = $this->paymentGatewayService->cancelSubscription($subscription);

            if ($canceled) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);

                return redirect()->route('member.subscription.index')
                    ->with('success', 'Subscription canceled successfully. It will remain active until the end of the current billing period.');
            } else {
                return redirect()->route('member.subscription.index')
                    ->with('error', 'Failed to cancel subscription. Please try again or contact support.');
            }
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('member.subscription.index')
                ->with('error', 'An error occurred while canceling the subscription. Please contact support.');
        }
    }

    /**
     * Handle subscription success (after payment).
     */
    public function success(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $sessionId = $request->query('session_id');
        $paymentIntentId = $request->query('payment_intent');
        $setupIntentId = $request->query('setup_intent');
        $razorpayPaymentId = $request->query('razorpay_payment_id');
        $subscriptionData = session('subscription_data');

        // Find the most recent pending subscription for this user
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($subscription) {
            // Verify and update subscription status based on gateway
            if ($subscription->gateway === 'stripe') {
                $this->verifyStripeSubscription($subscription, $paymentIntentId, $setupIntentId);
            } elseif ($subscription->gateway === 'razorpay' && $razorpayPaymentId) {
                $this->verifyRazorpaySubscription($subscription, $razorpayPaymentId);
            }
        }

        // Refresh subscription to get updated status
        if ($subscription) {
            $subscription->refresh();
        }

        return view('frontend.member.subscription.success', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Verify and update Stripe subscription status.
     */
    protected function verifyStripeSubscription(Subscription $subscription, ?string $paymentIntentId = null, ?string $setupIntentId = null): void
    {
        try {
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $stripe = new \Stripe\StripeClient($paymentSettings->stripe_secret_key);

            // If we have a subscription ID, check its status
            if ($subscription->gateway_subscription_id) {
                $stripeSubscription = $stripe->subscriptions->retrieve($subscription->gateway_subscription_id);
                
                $status = $this->mapStripeStatus($stripeSubscription->status);
                
                $subscription->update([
                    'status' => $status,
                    'trial_end_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                    'next_billing_at' => $stripeSubscription->current_period_end ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end) : null,
                    'started_at' => $stripeSubscription->start_date ? date('Y-m-d H:i:s', $stripeSubscription->start_date) : now(),
                ]);

                Log::info('Stripe subscription status verified', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscription->gateway_subscription_id,
                    'status' => $status,
                ]);
            } elseif ($paymentIntentId || $setupIntentId) {
                // Verify payment/setup intent status
                $intentId = $paymentIntentId ?? $setupIntentId;
                $intentType = $paymentIntentId ? 'payment_intent' : 'setup_intent';
                
                if ($intentType === 'payment_intent') {
                    $intent = $stripe->paymentIntents->retrieve($intentId);
                } else {
                    $intent = $stripe->setupIntents->retrieve($intentId);
                }

                if ($intent->status === 'succeeded') {
                    // Payment succeeded, update subscription to active or trialing
                    $plan = $subscription->subscriptionPlan;
                    $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                    
                    $subscription->update([
                        'status' => $status,
                        'started_at' => now(),
                    ]);

                    Log::info('Stripe payment verified, subscription activated', [
                        'subscription_id' => $subscription->id,
                        'intent_id' => $intentId,
                        'status' => $status,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify and update Razorpay subscription status.
     */
    protected function verifyRazorpaySubscription(Subscription $subscription, string $paymentId): void
    {
        try {
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $razorpay = new \Razorpay\Api\Api($paymentSettings->razorpay_key_id, $paymentSettings->razorpay_key_secret);
            
            $payment = $razorpay->payment->fetch($paymentId);
            
            if ($payment->status === 'captured' || $payment->status === 'authorized') {
                $plan = $subscription->subscriptionPlan;
                $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                
                $subscription->update([
                    'status' => $status,
                    'started_at' => now(),
                ]);

                Log::info('Razorpay payment verified, subscription activated', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify Razorpay subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map Stripe status to our status.
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match($stripeStatus) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled', 'unpaid' => 'canceled',
            default => 'pending',
        };
    }
}

