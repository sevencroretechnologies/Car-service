<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PriceLookupRequest;
use App\Http\Requests\Api\V1\VehicleServicePricingRequest;
use App\Services\BranchService;
use App\Services\VehicleServicePricingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class VehicleServicePricingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected VehicleServicePricingService $pricingService,
        protected BranchService $branchService
    ) {}

    /**
     * @OA\Get(
     *     path="/pricing",
     *     summary="List pricing rules",
     *     description="Get paginated list of vehicle service pricing rules",
     *     operationId="pricingIndex",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="branch_id", in="query", description="Filter by branch", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="service_id", in="query", description="Filter by service", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Pricing retrieved successfully"),
     *     @OA\Response(response=400, description="Branch ID is required"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $branchId = $request->input('branch_id', $user->branch_id);

            if (! $branchId) {
                return $this->errorResponse('Branch ID is required', 400);
            }

            $branch = $this->branchService->findByIdAndOrganization($branchId, $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Branch does not belong to your organization');
            }

            $perPage = $request->input('per_page', 15);
            $serviceId = $request->input('service_id');

            $pricing = $this->pricingService->getAll($branchId, $serviceId, $perPage);

            return $this->paginatedResponse($pricing, 'Pricing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pricing: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/pricing",
     *     summary="Create pricing rule",
     *     description="Create a new vehicle service pricing rule",
     *     operationId="pricingStore",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"branch_id", "service_id", "vehicle_type_id", "price"},
     *
     *         @OA\Property(property="branch_id", type="integer", example=1),
     *         @OA\Property(property="service_id", type="integer", example=1),
     *         @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *         @OA\Property(property="vehicle_brand_id", type="integer", description="Optional for brand-specific pricing"),
     *         @OA\Property(property="vehicle_model_id", type="integer", description="Optional for model-specific pricing"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Pricing created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=409, description="Duplicate pricing rule"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(VehicleServicePricingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            $branch = $this->branchService->findByIdAndOrganization($data['branch_id'], $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Branch does not belong to your organization');
            }

            $isDuplicate = $this->pricingService->checkDuplicatePricing(
                $data['branch_id'],
                $data['service_id'],
                $data['vehicle_type_id'],
                $data['vehicle_brand_id'] ?? null,
                $data['vehicle_model_id'] ?? null
            );

            if ($isDuplicate) {
                return $this->errorResponse('A pricing rule with these parameters already exists', 409);
            }

            $pricing = $this->pricingService->create($data);
            $pricing->load(['service', 'vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->createdResponse($pricing, 'Pricing created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create pricing: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/pricing/{id}",
     *     summary="Get pricing rule",
     *     description="Get pricing rule details by ID",
     *     operationId="pricingShow",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Pricing ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Pricing retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Pricing not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $pricing = $this->pricingService->findById($id);

            if (! $pricing) {
                return $this->notFoundResponse('Pricing not found');
            }

            $branch = $this->branchService->findByIdAndOrganization($pricing->branch_id, $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Pricing does not belong to your organization');
            }

            $pricing->load(['branch', 'service', 'vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->successResponse($pricing, 'Pricing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pricing: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/pricing/{id}",
     *     summary="Update pricing rule",
     *     description="Update pricing rule details",
     *     operationId="pricingUpdate",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Pricing ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"branch_id", "service_id", "vehicle_type_id", "price"},
     *
     *         @OA\Property(property="branch_id", type="integer"),
     *         @OA\Property(property="service_id", type="integer"),
     *         @OA\Property(property="vehicle_type_id", type="integer"),
     *         @OA\Property(property="vehicle_brand_id", type="integer"),
     *         @OA\Property(property="vehicle_model_id", type="integer"),
     *         @OA\Property(property="price", type="number", format="float"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="Pricing updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Pricing not found"),
     *     @OA\Response(response=409, description="Duplicate pricing rule"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(VehicleServicePricingRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $pricing = $this->pricingService->findById($id);

            if (! $pricing) {
                return $this->notFoundResponse('Pricing not found');
            }

            $branch = $this->branchService->findByIdAndOrganization($pricing->branch_id, $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Pricing does not belong to your organization');
            }

            $data = $request->validated();

            $isDuplicate = $this->pricingService->checkDuplicatePricing(
                $data['branch_id'],
                $data['service_id'],
                $data['vehicle_type_id'],
                $data['vehicle_brand_id'] ?? null,
                $data['vehicle_model_id'] ?? null,
                $id
            );

            if ($isDuplicate) {
                return $this->errorResponse('A pricing rule with these parameters already exists', 409);
            }

            $pricing = $this->pricingService->update($pricing, $data);
            $pricing->load(['service', 'vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->successResponse($pricing, 'Pricing updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update pricing: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/pricing/{id}",
     *     summary="Delete pricing rule",
     *     description="Soft delete a pricing rule",
     *     operationId="pricingDestroy",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Pricing ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Pricing deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Pricing not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $pricing = $this->pricingService->findById($id);

            if (! $pricing) {
                return $this->notFoundResponse('Pricing not found');
            }

            $branch = $this->branchService->findByIdAndOrganization($pricing->branch_id, $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Pricing does not belong to your organization');
            }

            $this->pricingService->delete($pricing);

            return $this->successResponse(null, 'Pricing deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete pricing: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/pricing/lookup",
     *     summary="Lookup price",
     *     description="Find the best matching price for a service and vehicle combination. Uses hierarchical lookup: exact model -> brand level -> type level",
     *     operationId="pricingLookup",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="branch_id", in="query", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="service_id", in="query", required=true, description="Service ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle_type_id", in="query", required=true, description="Vehicle Type ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle_brand_id", in="query", description="Vehicle Brand ID (optional)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="vehicle_model_id", in="query", description="Vehicle Model ID (optional)", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Price found", @OA\JsonContent(
     *
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="pricing", type="object"),
     *             @OA\Property(property="price", type="number", example=49.99),
     *             @OA\Property(property="match_type", type="string", enum={"exact_model", "brand_level", "type_level"})
     *         )
     *     )),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="No pricing found")
     * )
     */
    public function lookup(PriceLookupRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $branch = $this->branchService->findByIdAndOrganization($data['branch_id'], $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Branch does not belong to your organization');
            }

            $pricing = $this->pricingService->lookupPrice(
                $data['branch_id'],
                $data['service_id'],
                $data['vehicle_type_id'],
                $data['vehicle_brand_id'] ?? null,
                $data['vehicle_model_id'] ?? null
            );

            if (! $pricing) {
                return $this->notFoundResponse('No pricing found for the specified parameters');
            }

            $pricing->load(['service', 'vehicleType', 'vehicleBrand', 'vehicleModel']);

            return $this->successResponse([
                'pricing' => $pricing,
                'price' => $pricing->price,
                'match_type' => $this->getMatchType($pricing, $data),
            ], 'Price found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to lookup price: '.$e->getMessage());
        }
    }

    private function getMatchType(mixed $pricing, array $data): string
    {
        if ($pricing->vehicle_model_id && $pricing->vehicle_model_id == ($data['vehicle_model_id'] ?? null)) {
            return 'exact_model';
        }

        if ($pricing->vehicle_brand_id && $pricing->vehicle_brand_id == ($data['vehicle_brand_id'] ?? null)) {
            return 'brand_level';
        }

        return 'type_level';
    }

    /**
     * @OA\Get(
     *     path="/pricing/by-service/{serviceId}",
     *     summary="Get pricing by service",
     *     description="Get all pricing rules for a specific service",
     *     operationId="pricingGetByService",
     *     tags={"Pricing"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="serviceId", in="path", required=true, description="Service ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="branch_id", in="query", description="Branch ID (uses user's branch if not provided)", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Pricing retrieved successfully"),
     *     @OA\Response(response=400, description="Branch ID is required"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function getByService(Request $request, int $serviceId): JsonResponse
    {
        try {
            $user = $request->user();
            $branchId = $request->input('branch_id', $user->branch_id);

            if (! $branchId) {
                return $this->errorResponse('Branch ID is required', 400);
            }

            $branch = $this->branchService->findByIdAndOrganization($branchId, $user->org_id);
            if (! $branch) {
                return $this->forbiddenResponse('Branch does not belong to your organization');
            }

            $pricing = $this->pricingService->getPricingByService($branchId, $serviceId);

            return $this->successResponse($pricing, 'Pricing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pricing: '.$e->getMessage());
        }
    }
}
