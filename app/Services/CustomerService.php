<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function getAll(int $orgId, ?int $branchId = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::where('org_id', $orgId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function findByIdAndOrganization(int $id, int $orgId): ?Customer
    {
        return Customer::where('id', $id)
            ->where('org_id', $orgId)
            ->first();
    }

    public function findByPhone(string $phone, int $orgId): ?Customer
    {
        return Customer::where('phone', $phone)
            ->where('org_id', $orgId)
            ->first();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->fresh();
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}
