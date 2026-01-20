<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ServiceService
{
    public function getAll(int $orgId, ?int $branchId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Service::where('org_id', $orgId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getAllByBranch(int $branchId): Collection
    {
        return Service::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Service
    {
        return Service::find($id);
    }

    public function findByIdAndOrganization(int $id, int $orgId): ?Service
    {
        return Service::where('id', $id)
            ->where('org_id', $orgId)
            ->first();
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        return $service->delete();
    }
}
