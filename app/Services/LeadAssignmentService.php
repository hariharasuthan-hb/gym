<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;

class LeadAssignmentService
{
    /**
     * Maximum number of leads a trainer should have before considering them "full"
     */
    private const MAX_LEADS_PER_TRAINER = 5;

    /**
     * Automatically assign a lead to a trainer based on load balancing rules.
     * 
     * Rules:
     * 1. Always prioritize trainers with the **lowest** number of active leads
     *    (so trainers with 0 active leads are filled first).
     * 2. As long as at least one trainer is under MAX_LEADS_PER_TRAINER, only
     *    trainers under that limit are considered.
     * 3. When multiple trainers share the same lowest count, pick one randomly
     *    so leads are distributed as equally as possible.
     * 
     * @return int|null The trainer ID to assign, or null if no trainers available
     */
    public function assignLeadAutomatically(): ?int
    {
        $trainers = $this->getAvailableTrainers();

        if ($trainers->isEmpty()) {
            return null;
        }

        // Build a collection of trainers with their active lead counts
        $trainersWithCounts = $this->getTrainersWithActiveLeadCounts($trainers);

        if ($trainersWithCounts->isEmpty()) {
            return null;
        }

        // First, try to use only trainers who are under the MAX_LEADS_PER_TRAINER limit
        $eligible = $trainersWithCounts->filter(function (array $item) {
            return $item['lead_count'] < self::MAX_LEADS_PER_TRAINER;
        });

        // If everyone is already at or above the limit, fall back to all trainers
        if ($eligible->isEmpty()) {
            $eligible = $trainersWithCounts;
        }

        // Find the minimum active lead count among eligible trainers
        $minCount = $eligible->min('lead_count');

        // From trainers with this minimum count, pick one at random
        $candidates = $eligible
            ->where('lead_count', $minCount)
            ->pluck('trainer');

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->random()->id;
    }

    /**
     * Get all active trainers (users with trainer role).
     * 
     * @return Collection
     */
    private function getAvailableTrainers(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'trainer');
        })
        ->where('status', 'active')
        ->get();
    }

    /**
     * Build a collection of trainers with their active lead counts.
     * 
     * @param Collection $trainers
     * @return Collection
     */
    private function getTrainersWithActiveLeadCounts(Collection $trainers): Collection
    {
        return $trainers->map(function ($trainer) {
            $leadCount = Lead::where('assigned_to', $trainer->id)
                ->whereIn('status', [
                    Lead::STATUS_NEW,
                    Lead::STATUS_CONTACTED,
                    Lead::STATUS_QUALIFIED,
                ])
                ->count();

            return [
                'trainer'    => $trainer,
                'lead_count' => $leadCount,
            ];
        });
    }

    /**
     * Get the trainer with the least number of assigned leads.
     * 
     * @param Collection $trainers
     * @return User|null
     */
    private function getTrainerWithLeastLeads(Collection $trainers): ?User
    {
        $trainersWithCounts = $trainers->map(function ($trainer) {
            $leadCount = Lead::where('assigned_to', $trainer->id)
                ->whereIn('status', [
                    Lead::STATUS_NEW,
                    Lead::STATUS_CONTACTED,
                    Lead::STATUS_QUALIFIED
                ])
                ->count();

            return [
                'trainer' => $trainer,
                'lead_count' => $leadCount,
            ];
        });

        // Sort by lead count ascending
        $sorted = $trainersWithCounts->sortBy('lead_count');

        // Get trainers with the minimum lead count
        $minCount = $sorted->first()['lead_count'];
        $trainersWithMinCount = $sorted->where('lead_count', $minCount)->pluck('trainer');

        // Randomly select from trainers with minimum lead count
        return $trainersWithMinCount->random();
    }

    /**
     * Get lead count for a specific trainer.
     * 
     * @param int $trainerId
     * @return int
     */
    public function getLeadCountForTrainer(int $trainerId): int
    {
        return Lead::where('assigned_to', $trainerId)
            ->whereIn('status', [
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_QUALIFIED
            ])
            ->count();
    }

    /**
     * Check if a trainer can accept more leads.
     * 
     * @param int $trainerId
     * @return bool
     */
    public function canTrainerAcceptLeads(int $trainerId): bool
    {
        return $this->getLeadCountForTrainer($trainerId) < self::MAX_LEADS_PER_TRAINER;
    }
}
