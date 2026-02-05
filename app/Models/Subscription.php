<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    /**
     * Subscription status constants
     */
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    /**
     * Payment gateway constants
     */
    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_RAZORPAY = 'razorpay';

    /**
     * Get all available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_TRIALING,
            self::STATUS_ACTIVE,
            self::STATUS_CANCELED,
            self::STATUS_PAST_DUE,
            self::STATUS_EXPIRED,
            self::STATUS_PENDING,
        ];
    }

    /**
     * Get all available gateway options
     */
    public static function getGatewayOptions(): array
    {
        return [
            self::GATEWAY_STRIPE,
            self::GATEWAY_RAZORPAY,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'gateway',
        'gateway_customer_id',
        'gateway_subscription_id',
        'status',
        'trial_end_at',
        'next_billing_at',
        'expiration_at',
        'started_at',
        'canceled_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_end_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'started_at' => 'datetime',
            'canceled_at' => 'datetime',
            'expiration_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the payments for the subscription.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
            ->where(function ($q) {
                $q->whereNull('next_billing_at')
                  ->orWhere('next_billing_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('trial_end_at')
                  ->orWhere('trial_end_at', '>=', now());
            })
            ->notExpired();
    }

    /**
     * Scope a query to only include non-expired subscriptions.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiration_at')
              ->orWhere('expiration_at', '>', now());
        });
    }

    /**
     * Scope a query to only include active and non-expired subscriptions.
     */
    public function scopeActiveAndNotExpired($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Check if subscription is in trial period.
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing' 
            && $this->trial_end_at 
            && $this->trial_end_at->isFuture();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (!$this->next_billing_at || $this->next_billing_at->isFuture())
            && !$this->isExpired();
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED || $this->canceled_at !== null;
    }

    /**
     * Check if subscription is expired based on expiration_at or status.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expiration_at instanceof Carbon && $this->expiration_at->isPast();
    }

    /**
     * Check if user still has access to subscription.
     * Returns true if subscription is active/trialing OR if canceled but period hasn't ended yet.
     */
    public function hasAccess(): bool
    {
        // Only grant access for non-expired active or trialing subscriptions
        if ($this->isExpired()) {
            return false;
        }

        return $this->isActive() || $this->isTrialing();
    }

    /**
     * Calculate expiration date based purely on start date and plan duration.
     */
    public static function calculateExpiration(
        ?SubscriptionPlan $plan,
        ?Carbon $startedAt,
        ?Carbon $trialEndAt = null,
        ?Carbon $nextBillingAt = null
    ): ?Carbon {
        if (!$plan || !$startedAt) {
            return null;
        }

        $base = $startedAt->copy();

        return match ($plan->duration_type) {
            'daily' => $base->addDays($plan->duration),
            'weekly' => $base->addWeeks($plan->duration),
            'monthly' => $base->addMonths($plan->duration),
            'yearly' => $base->addYears($plan->duration),
            'trial' => $base->addDays($plan->duration ?? ($plan->trial_days ?? 0)),
            default => null,
        };
    }
}
