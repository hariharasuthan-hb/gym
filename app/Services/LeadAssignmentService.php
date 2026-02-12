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
     * 1. Prioritize trainers with less than 5 leads
     * 2. If all trainers have 5+ leads, distribute randomly/equally
     * 
     * @return int|null The trainer ID to assign, or null if no trainers available
     */
    public function assignLeadAutomatically(): ?int
    {
        $trainers = $this->getAvailableTrainers();

        if ($trainers->isEmpty()) {
            return null;
        }

        // Get trainers with less than MAX_LEADS_PER_TRAINER leads
        $availableTrainers = $this->getTrainersWithLessThanMaxLeads($trainers);

        if ($availableTrainers->isNotEmpty()) {
            // Randomly select from trainers with less than 5 leads
            return $availableTrainers->random()->id;
        }

        // All trainers have 5+ leads, distribute equally by selecting trainer with least leads
        return $this->getTrainerWithLeastLeads($trainers)?->id;
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
     * Get trainers who have less than MAX_LEADS_PER_TRAINER leads assigned.
     * 
     * @param Collection $trainers
     * @return Collection
     */
    private function getTrainersWithLessThanMaxLeads(Collection $trainers): Collection
    {
        return $trainers->filter(function ($trainer) {
            $leadCount = Lead::where('assigned_to', $trainer->id)
                ->whereIn('status', [
                    Lead::STATUS_NEW,
                    Lead::STATUS_CONTACTED,
                    Lead::STATUS_QUALIFIED
                ])
                ->count();

            return $leadCount < self::MAX_LEADS_PER_TRAINER;
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
