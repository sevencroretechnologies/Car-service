<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRequest;
use App\Services\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * @OA\Get(
     *     path="/customers",
     *     summary="List customers",
     *     description="Get paginated list of customers",
     *     operationId="customersIndex",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="branch_id", in="query", description="Filter by branch", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search by name/phone/email", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Customers retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);
            $branchId = $request->input('branch_id');
            $search = $request->input('search');

            $customers = $this->customerService->getAll(
                $user->org_id,
                $branchId,
                $search,
                $perPage
            );

            return $this->paginatedResponse($customers, 'Customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customers: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/customers",
     *     summary="Create customer",
     *     description="Create a new customer",
     *     operationId="customersStore",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "name", "phone"},
     *
     *         @OA\Property(property="org_id", type="integer", example=1),
     *         @OA\Property(property="branch_id", type="integer"),
     *         @OA\Property(property="name", type="string", example="John Customer"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string", example="+1234567890"),
     *         @OA\Property(property="address", type="string"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Customer created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CustomerRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            if ($data['org_id'] != $user->org_id) {
                return $this->forbiddenResponse('You can only create customers for your organization');
            }

            $customer = $this->customerService->create($data);

            return $this->createdResponse($customer, 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create customer: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/customers/{id}",
     *     summary="Get customer",
     *     description="Get customer details by ID",
     *     operationId="customersShow",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($id, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $customer->load(['organization', 'branch', 'vehicles.vehicleType', 'vehicles.vehicleBrand', 'vehicles.vehicleModel']);

            return $this->successResponse($customer, 'Customer retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/customers/{id}",
     *     summary="Update customer",
     *     description="Update customer details",
     *     operationId="customersUpdate",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "name", "phone"},
     *
     *         @OA\Property(property="org_id", type="integer"),
     *         @OA\Property(property="branch_id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="address", type="string"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="Customer updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(CustomerRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($id, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $customer = $this->customerService->update($customer, $request->validated());

            return $this->successResponse($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update customer: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/customers/{id}",
     *     summary="Delete customer",
     *     description="Soft delete a customer",
     *     operationId="customersDestroy",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($id, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $this->customerService->delete($customer);

            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete customer: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/customers/search",
     *     summary="Search customer by phone",
     *     description="Find a customer by phone number",
     *     operationId="customersSearchByPhone",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="phone", in="query", required=true, description="Phone number", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Customer found"),
     *     @OA\Response(response=400, description="Phone number is required"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $phone = $request->input('phone');

            if (! $phone) {
                return $this->errorResponse('Phone number is required', 400);
            }

            $customer = $this->customerService->findByPhone($phone, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $customer->load(['vehicles.vehicleType', 'vehicles.vehicleBrand', 'vehicles.vehicleModel']);

            return $this->successResponse($customer, 'Customer found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to search customer: '.$e->getMessage());
        }
    }
}
