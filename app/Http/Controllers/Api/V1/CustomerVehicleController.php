<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerVehicleRequest;
use App\Services\CustomerService;
use App\Services\CustomerVehicleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CustomerVehicleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CustomerVehicleService $customerVehicleService,
        protected CustomerService $customerService
    ) {}

    /**
     * @OA\Get(
     *     path="/customers/{customer}/vehicles",
     *     summary="List customer vehicles",
     *     description="Get paginated list of vehicles for a customer",
     *     operationId="customerVehiclesIndex",
     *     tags={"Customer Vehicles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="customer", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(response=200, description="Customer vehicles retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function index(Request $request, int $customerId): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($customerId, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $perPage = $request->input('per_page', 15);
            $vehicles = $this->customerVehicleService->getAll($customerId, $perPage);

            return $this->paginatedResponse($vehicles, 'Customer vehicles retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer vehicles: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/customer-vehicles",
     *     summary="Create customer vehicle",
     *     description="Add a new vehicle for a customer",
     *     operationId="customerVehiclesStore",
     *     tags={"Customer Vehicles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_id", "vehicle_type_id", "vehicle_brand_id", "vehicle_model_id", "registration_number"},
     *
     *         @OA\Property(property="customer_id", type="integer", example=1),
     *         @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *         @OA\Property(property="vehicle_brand_id", type="integer", example=1),
     *         @OA\Property(property="vehicle_model_id", type="integer", example=1),
     *         @OA\Property(property="registration_number", type="string", example="ABC-1234"),
     *         @OA\Property(property="color", type="string", example="White"),
     *         @OA\Property(property="year", type="integer", example=2023),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *
     *     @OA\Response(response=201, description="Customer vehicle created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CustomerVehicleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            $customer = $this->customerService->findByIdAndOrganization($data['customer_id'], $user->org_id);
            if (! $customer) {
                return $this->forbiddenResponse('Customer does not belong to your organization');
            }

            $vehicle = $this->customerVehicleService->create($data);
            $vehicle->load(['vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->createdResponse($vehicle, 'Customer vehicle created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create customer vehicle: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/customers/{customer}/vehicles/{vehicle}",
     *     summary="Get customer vehicle",
     *     description="Get customer vehicle details by ID",
     *     operationId="customerVehiclesShow",
     *     tags={"Customer Vehicles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="customer", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle", in="path", required=true, description="Vehicle ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer vehicle retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer or vehicle not found")
     * )
     */
    public function show(Request $request, int $customerId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($customerId, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $vehicle = $this->customerVehicleService->findByIdAndCustomer($id, $customerId);

            if (! $vehicle) {
                return $this->notFoundResponse('Customer vehicle not found');
            }

            $vehicle->load(['customer', 'vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->successResponse($vehicle, 'Customer vehicle retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer vehicle: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/customers/{customer}/vehicles/{vehicle}",
     *     summary="Update customer vehicle",
     *     description="Update customer vehicle details",
     *     operationId="customerVehiclesUpdate",
     *     tags={"Customer Vehicles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="customer", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle", in="path", required=true, description="Vehicle ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_id", "vehicle_type_id", "vehicle_brand_id", "vehicle_model_id", "registration_number"},
     *
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="vehicle_type_id", type="integer"),
     *         @OA\Property(property="vehicle_brand_id", type="integer"),
     *         @OA\Property(property="vehicle_model_id", type="integer"),
     *         @OA\Property(property="registration_number", type="string"),
     *         @OA\Property(property="color", type="string"),
     *         @OA\Property(property="year", type="integer"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *
     *     @OA\Response(response=200, description="Customer vehicle updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer or vehicle not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(CustomerVehicleRequest $request, int $customerId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($customerId, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $vehicle = $this->customerVehicleService->findByIdAndCustomer($id, $customerId);

            if (! $vehicle) {
                return $this->notFoundResponse('Customer vehicle not found');
            }

            $vehicle = $this->customerVehicleService->update($vehicle, $request->validated());
            $vehicle->load(['vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->successResponse($vehicle, 'Customer vehicle updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update customer vehicle: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/customers/{customer}/vehicles/{vehicle}",
     *     summary="Delete customer vehicle",
     *     description="Soft delete a customer vehicle",
     *     operationId="customerVehiclesDestroy",
     *     tags={"Customer Vehicles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="customer", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle", in="path", required=true, description="Vehicle ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer vehicle deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Customer or vehicle not found")
     * )
     */
    public function destroy(Request $request, int $customerId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $this->customerService->findByIdAndOrganization($customerId, $user->org_id);

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $vehicle = $this->customerVehicleService->findByIdAndCustomer($id, $customerId);

            if (! $vehicle) {
                return $this->notFoundResponse('Customer vehicle not found');
            }

            $this->customerVehicleService->delete($vehicle);

            return $this->successResponse(null, 'Customer vehicle deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete customer vehicle: '.$e->getMessage());
        }
    }
}
