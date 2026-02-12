<?php

namespace App\Repositories\Eloquent;

use App\Models\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    /**
     * Get query builder with eager-loaded relations for listing.
     */
    public function queryWithRelations(): Builder
    {
        return $this->model->newQuery()
            ->with(['assignedTo', 'createdBy']);
    }

    /**
     * Get leads by status.
     */
    public function getByStatus(string $status): Builder
    {
        return $this->model->newQuery()->where('status', $status);
    }

    /**
     * Get leads by source.
     */
    public function getBySource(string $source): Builder
    {
        return $this->model->newQuery()->where('source', $source);
    }

    /**
     * Get leads assigned to a specific user.
     */
    public function getAssignedTo(int $userId): Builder
    {
        return $this->model->newQuery()->where('assigned_to', $userId);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        // Filter by status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // Filter by source
        if (isset($filters['source']) && $filters['source'] !== '') {
            $query->where('source', $filters['source']);
        }

        // Filter by assigned_to
        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Filter by date range (created_at)
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by follow_up_date
        if (isset($filters['follow_up_date_from']) && $filters['follow_up_date_from'] !== '') {
            $query->whereDate('follow_up_date', '>=', $filters['follow_up_date_from']);
        }

        if (isset($filters['follow_up_date_to']) && $filters['follow_up_date_to'] !== '') {
            $query->whereDate('follow_up_date', '<=', $filters['follow_up_date_to']);
        }

        return $query;
    }

    /**
     * Apply search to query
     */
    protected function applySearch($query, mixed $search)
    {
        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Get searchable columns
     */
    protected function getSearchableColumns(): array
    {
        return ['name', 'email', 'phone'];
    }
}
