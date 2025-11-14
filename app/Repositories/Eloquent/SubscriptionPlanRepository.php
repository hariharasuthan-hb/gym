<?php

namespace App\Repositories\Eloquent;

use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\SubscriptionPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPlanRepository extends BaseRepository implements SubscriptionPlanRepositoryInterface
{
    public function __construct(SubscriptionPlan $model)
    {
        parent::__construct($model);
    }

    /**
     * Get query builder for active plans.
     */
    public function queryActive(): Builder
    {
        return $this->model->newQuery()->where('is_active', true);
    }

    /**
     * Get plans by duration type.
     */
    public function queryByDurationType(string $durationType): Builder
    {
        return $this->model->newQuery()->where('duration_type', $durationType);
    }

    /**
     * Apply search filter.
     */
    protected function applySearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('plan_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}

