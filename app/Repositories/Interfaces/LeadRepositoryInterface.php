<?php

namespace App\Repositories\Interfaces;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;

interface LeadRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get query builder with eager-loaded relations for listing.
     */
    public function queryWithRelations(): Builder;

    /**
     * Get leads by status.
     */
    public function getByStatus(string $status): Builder;

    /**
     * Get leads by source.
     */
    public function getBySource(string $source): Builder;

    /**
     * Get leads assigned to a specific user.
     */
    public function getAssignedTo(int $userId): Builder;
}
