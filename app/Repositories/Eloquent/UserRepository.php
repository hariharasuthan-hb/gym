<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function queryWithRoles(): Builder
    {
        return $this->model->newQuery()->with('roles');
    }

    public function createWithRole(array $data): User
    {
        $roleId = $data['role'] ?? null;
        unset($data['role']);

        $user = $this->create($data);
        $this->syncRole($user, $roleId);

        return $user;
    }

    public function updateWithRole(User $user, array $data): bool
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $roleId = $data['role'] ?? null;
        unset($data['role']);

        $updated = $user->update($data);
        $this->syncRole($user, $roleId);

        return $updated;
    }

    public function deleteByModel(User $user): bool
    {
        return $user->delete();
    }

    protected function syncRole(User $user, ?int $roleId): void
    {
        if (!$roleId) {
            $user->syncRoles([]);
            return;
        }

        $role = Role::findOrFail($roleId);
        $user->syncRoles([$role->name]);
    }

    protected function applySearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}

