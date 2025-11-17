<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\TrainerFilterService;
use Illuminate\Support\Collection;

class ExportSubscriptionsJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $query = Subscription::query()->with(['user', 'subscriptionPlan']);

        // Apply trainer filter if user is a trainer
        if (!empty($this->trainerContext['is_trainer']) && !empty($this->trainerContext['member_ids'])) {
            $query->whereIn('user_id', $this->trainerContext['member_ids']);
        }

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['gateway'])) {
            $query->where('gateway', $this->filters['gateway']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('subscriptionPlan', function ($planQuery) use ($search) {
                    $planQuery->where('plan_name', 'like', "%{$search}%");
                });
            });
        }

        // Process in chunks to handle large datasets
        $allData = collect();
        $query->chunk(1000, function ($chunk) use (&$allData) {
            $allData = $allData->merge($chunk);
        });

        return $allData;
    }

    /**
     * Get the column headers for the export.
     */
    protected function getHeaders(): array
    {
        return [
            'Subscription ID',
            'User Name',
            'User Email',
            'Plan Name',
            'Status',
            'Gateway',
            'Trial End At',
            'Next Billing At',
            'Started At',
            'Canceled At',
            'Created At',
        ];
    }

    /**
     * Format a single row for export.
     */
    protected function formatRow($row): array
    {
        return [
            $row->id,
            $row->user->name ?? '—',
            $row->user->email ?? '—',
            $row->subscriptionPlan->plan_name ?? '—',
            ucfirst(str_replace('_', ' ', $row->status ?? 'unknown')),
            ucfirst($row->gateway ?? '—'),
            $row->trial_end_at ? $row->trial_end_at->format('M d, Y h:i A') : '—',
            $row->next_billing_at ? $row->next_billing_at->format('M d, Y h:i A') : '—',
            $row->started_at ? $row->started_at->format('M d, Y h:i A') : '—',
            $row->canceled_at ? $row->canceled_at->format('M d, Y h:i A') : '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

