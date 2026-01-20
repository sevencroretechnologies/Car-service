<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BranchService
{
    public function getAll(int $orgId, int $perPage = 15): LengthAwarePaginator
    {
        return Branch::where('org_id', $orgId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getAllWithoutPagination(int $orgId): Collection
    {
        return Branch::where('org_id', $orgId)
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Branch
    {
        return Branch::find($id);
    }

    public function findByIdAndOrganization(int $id, int $orgId): ?Branch
    {
        return Branch::where('id', $id)
            ->where('org_id', $orgId)
            ->first();
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->fresh();
    }

    public function delete(Branch $branch): bool
    {
        return $branch->delete();
    }
}
