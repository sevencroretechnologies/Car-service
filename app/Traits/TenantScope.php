<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait TenantScope
{
    /**
     * Apply tenant scoping to a query based on the authenticated user.
     *
     * - Always filters by org_id
     * - Conditionally filters by branch_id if the user has a branch_id (branch-level user)
     * - Org-level users (no branch_id) get organization-wide data
     *
     * @param  Builder  $query  The query builder instance
     * @param  User  $user  The authenticated user
     * @param  string  $orgColumn  The column name for organization ID (default: 'org_id')
     * @param  string  $branchColumn  The column name for branch ID (default: 'branch_id')
     */
    protected function applyTenantScope(
        Builder $query,
        User $user,
        string $orgColumn = 'org_id',
        string $branchColumn = 'branch_id'
    ): Builder {
        return $query
            ->where($orgColumn, $user->org_id)
            ->when($user->branch_id, function (Builder $q) use ($user, $branchColumn) {
                return $q->where($branchColumn, $user->branch_id);
            });
    }

    /**
     * Check if a record belongs to the user's tenant scope.
     *
     * @param  mixed  $model  The model instance to check
     * @param  User  $user  The authenticated user
     * @param  string  $orgColumn  The column name for organization ID (default: 'org_id')
     * @param  string  $branchColumn  The column name for branch ID (default: 'branch_id')
     */
    protected function belongsToTenant(
        $model,
        User $user,
        string $orgColumn = 'org_id',
        string $branchColumn = 'branch_id'
    ): bool {
        if ($model->{$orgColumn} !== $user->org_id) {
            return false;
        }

        if ($user->branch_id && $model->{$branchColumn} !== $user->branch_id) {
            return false;
        }

        return true;
    }
}
