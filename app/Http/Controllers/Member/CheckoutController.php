<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGateway\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {
        // Middleware is applied in routes/frontend.php
    }

    /**
     * Show checkout page for subscription plan.
     */
    public function checkout(SubscriptionPlan $plan): View|RedirectResponse
    {
        $user = auth()->user();

        // Check if user already has an active subscription
        $activeSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->first();

        if ($activeSubscription) {
            return redirect()->route('member.dashboard')
                ->with('info', 'You already have an active subscription.');
        }

        // Check if plan is active
        if (!$plan->is_active) {
            return redirect()->route('member.dashboard')
                ->with('error', 'This subscription plan is not available.');
        }

        // Get available payment gateways
        $availableGateways = $this->paymentGatewayService->getAvailableGateways();

        if (empty($availableGateways)) {
            return redirect()->route('member.dashboard')
                ->with('error', 'No payment gateways are configured. Please contact support.');
        }

        // Get payment settings for Google Pay check
        $paymentSettings = \App\Models\PaymentSetting::getSettings();

        return view('frontend.member.subscription.checkout', [
            'plan' => $plan,
            'availableGateways' => $availableGateways,
            'hasTrial' => $plan->hasTrial(),
            'trialDays' => $plan->getTrialDays(),
            'enableGpay' => $paymentSettings->enable_gpay ?? false,
        ]);
    }

    /**
     * Create subscription and redirect to payment.
     */
    public function create(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $user = auth()->user();

        // Debug: Log incoming request
        Log::info('🔵 CheckoutController::create - Request received', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'request_method' => $request->method(),
            'request_data' => $request->all(),
            'has_gateway' => $request->has('gateway'),
            'gateway_value' => $request->input('gateway'),
        ]);

        try {
            $validated = $request->validate([
                'gateway' => ['required', 'in:stripe,razorpay'],
            ], [
                'gateway.required' => 'Please select a payment method.',
                'gateway.in' => 'Invalid payment gateway selected.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->route('member.subscription.checkout', $plan->id)
                ->withErrors($e->errors())
                ->withInput();
        }

        // Check if user already has an active subscription
        $activeSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->first();

        if ($activeSubscription) {
            return redirect()->route('member.dashboard')
                ->with('info', 'You already have an active subscription.');
        }

        try {
            $result = $this->paymentGatewayService->createSubscription(
                $user,
                $plan,
                $validated['gateway']
            );

            $result['gateway'] = $validated['gateway'];

            // Save subscription to database immediately
            $trialEndAt = null;
            if (isset($result['trial_end']) && $result['trial_end']) {
                try {
                    $trialEndAt = is_string($result['trial_end']) 
                        ? \Carbon\Carbon::parse($result['trial_end']) 
                        : $result['trial_end'];
                } catch (\Exception $e) {
                    // If parsing fails, leave as null
                    Log::warning('Failed to parse trial_end', ['trial_end' => $result['trial_end'] ?? null]);
                }
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'gateway' => $validated['gateway'],
                'gateway_customer_id' => $result['customer_id'] ?? null,
                'gateway_subscription_id' => $result['subscription_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'trial_end_at' => $trialEndAt,
                'started_at' => now(),
                'metadata' => $result,
            ]);

            session([
                'subscription_data' => [
                    'plan_id' => $plan->id,
                    'gateway' => $validated['gateway'],
                    'result' => $result,
                    'subscription_id' => $subscription->id,
                ],
            ]);

            $request->session()->put('payment_data', $result);
            session()->save();
            
            return redirect()->route('member.subscription.checkout', $plan->id);

        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $validated['gateway'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide user-friendly error message
            $errorMessage = 'A processing error occurred. Please try again.';
            
            // If it's a Stripe API error, provide more specific message
            if (str_contains($e->getMessage(), 'Stripe') || str_contains($e->getMessage(), 'stripe')) {
                $errorMessage = 'Payment gateway error. Please check your payment details and try again.';
            }

            return redirect()->route('member.subscription.checkout', $plan->id)
                ->with('error', $errorMessage);
        }
    }
}
