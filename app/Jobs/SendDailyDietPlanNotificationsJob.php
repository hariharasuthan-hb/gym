<?php

namespace App\Jobs;

use App\Models\DietPlan;
use App\Models\InAppNotification;
use App\Services\InAppNotificationDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendDailyDietPlanNotificationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Target date for the notification run (Y-m-d).
     */
    private string $targetDate;

    public function __construct(?string $targetDate = null)
    {
        $this->targetDate = $targetDate ?: now()->toDateString();
    }

    public function handle(InAppNotificationDispatcher $dispatcher): void
    {
        $runDate = CarbonImmutable::parse($this->targetDate)->startOfDay();

        DietPlan::query()
            ->with(['member.activeSubscription', 'trainer'])
            ->active()
            ->whereDate('start_date', '<=', $runDate->toDateString())
            ->where(function ($query) use ($runDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $runDate->toDateString());
            })
            ->whereHas('member', function ($memberQuery) {
                $memberQuery->where('status', 'active')
                    ->whereHas('activeSubscription');
            })
            ->orderBy('id')
            ->chunkById(100, function (Collection $plans) use ($runDate, $dispatcher) {
                $plans->each(function (DietPlan $plan) use ($runDate, $dispatcher) {
                    $member = $plan->member;

                    if (! $member) {
                        return;
                    }

                    $title = $this->buildTitle($plan, $runDate);

                    if ($this->alreadySent($member->getKey(), $title, $runDate)) {
                        return;
                    }

                    $authorId = $plan->trainer?->getKey();

                    if (! $authorId) {
                        return;
                    }

                    $notification = InAppNotification::create([
                        'title' => $title,
                        'message' => $this->buildMessage($plan, $runDate),
                        'audience_type' => InAppNotification::AUDIENCE_USER,
                        'target_user_id' => $member->getKey(),
                        'status' => InAppNotification::STATUS_PUBLISHED,
                        'published_at' => now(),
                        'created_by' => $authorId,
                        'updated_by' => $authorId,
                    ]);

                    $dispatcher->dispatch($notification);
                });
            });
    }

    /**
     * Build a deterministic title so duplicated runs in the same day are ignored.
     */
    protected function buildTitle(DietPlan $plan, CarbonImmutable $runDate): string
    {
        return sprintf(
            'Daily Diet Plan • %s • %s',
            $plan->plan_name,
            $runDate->isoFormat('MMM D, YYYY')
        );
    }

    /**
     * Generate a concise, markdown-friendly message following the notification standards.
     */
    protected function buildMessage(DietPlan $plan, CarbonImmutable $runDate): string
    {
        $segments = [
            sprintf('Date: %s', $runDate->isoFormat('dddd, MMM D')),
            sprintf('Focus: %s', $this->cleanText($plan->nutritional_goals) ?: 'Follow today’s structured meals.'),
        ];

        if ($plan->target_calories) {
            $segments[] = sprintf('Target Calories: %s cal', number_format((int) $plan->target_calories));
        }

        if ($mealSummary = $this->formatMeals($plan)) {
            $segments[] = 'Meals: '.$mealSummary;
        }

        if ($notes = $this->cleanText($plan->notes)) {
            $segments[] = 'Notes: '.$notes;
        }

        return implode(' • ', array_filter($segments));
    }

    protected function formatMeals(DietPlan $plan): ?string
    {
        $meals = $plan->meal_plan;

        if (empty($meals)) {
            return null;
        }

        if (! is_array($meals)) {
            $decoded = json_decode($meals, true);
            $meals = $decoded ?? [$meals];
        }

        $items = collect($meals)
            ->map(function ($meal, int $index) {
                $value = is_array($meal)
                    ? collect($meal)
                        ->map(function ($item, $key) {
                            return is_string($key)
                                ? ucfirst($key).': '.$item
                                : $item;
                        })
                        ->filter()
                        ->implode('; ')
                    : $meal;

                $value = $this->cleanText($value);

                return $value ? sprintf('%d) %s', $index + 1, $value) : null;
            })
            ->filter()
            ->implode(' | ');

        return $items ?: null;
    }

    protected function cleanText(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = preg_replace("/\s+/u", ' ', trim($value));

        return $normalized ?: null;
    }

    protected function alreadySent(int $userId, string $title, CarbonImmutable $runDate): bool
    {
        return InAppNotification::query()
            ->where('audience_type', InAppNotification::AUDIENCE_USER)
            ->where('target_user_id', $userId)
            ->where('title', $title)
            ->whereDate('created_at', $runDate->toDateString())
            ->exists();
    }
}


