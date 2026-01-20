<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequest;
use App\Services\ServiceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ServiceService $serviceService
    ) {}

    /**
     * @OA\Get(
     *     path="/services",
     *     summary="List services",
     *     description="Get paginated list of services",
     *     operationId="servicesIndex",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="branch_id", in="query", description="Filter by branch", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Services retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);
            $branchId = $request->input('branch_id');

            $services = $this->serviceService->getAll($user->org_id, $branchId, $perPage);

            return $this->paginatedResponse($services, 'Services retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve services: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/services/by-branch/{branchId}",
     *     summary="List services by branch",
     *     description="Get all services for a specific branch",
     *     operationId="servicesListByBranch",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="branchId", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Services retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listByBranch(int $branchId): JsonResponse
    {
        try {
            $services = $this->serviceService->getAllByBranch($branchId);

            return $this->successResponse($services, 'Services retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve services: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/services",
     *     summary="Create service",
     *     description="Create a new service",
     *     operationId="servicesStore",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "branch_id", "name"},
     *
     *         @OA\Property(property="org_id", type="integer", example=1),
     *         @OA\Property(property="branch_id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="Full Car Wash"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="base_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="duration_minutes", type="integer", example=60),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Service created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ServiceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            if ($data['org_id'] != $user->org_id) {
                return $this->forbiddenResponse('You can only create services for your organization');
            }

            $service = $this->serviceService->create($data);

            return $this->createdResponse($service, 'Service created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create service: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/services/{id}",
     *     summary="Get service",
     *     description="Get service details by ID",
     *     operationId="servicesShow",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Service ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Service retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Service not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $service = $this->serviceService->findByIdAndOrganization($id, $user->org_id);

            if (! $service) {
                return $this->notFoundResponse('Service not found');
            }

            $service->load(['organization', 'branch', 'vehicleServicePricing']);

            return $this->successResponse($service, 'Service retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve service: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/services/{id}",
     *     summary="Update service",
     *     description="Update service details",
     *     operationId="servicesUpdate",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Service ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "branch_id", "name"},
     *
     *         @OA\Property(property="org_id", type="integer"),
     *         @OA\Property(property="branch_id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="base_price", type="number", format="float"),
     *         @OA\Property(property="duration_minutes", type="integer"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="Service updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Service not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(ServiceRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $service = $this->serviceService->findByIdAndOrganization($id, $user->org_id);

            if (! $service) {
                return $this->notFoundResponse('Service not found');
            }

            $service = $this->serviceService->update($service, $request->validated());

            return $this->successResponse($service, 'Service updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update service: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/services/{id}",
     *     summary="Delete service",
     *     description="Soft delete a service",
     *     operationId="servicesDestroy",
     *     tags={"Services"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Service ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Service deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Service not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $service = $this->serviceService->findByIdAndOrganization($id, $user->org_id);

            if (! $service) {
                return $this->notFoundResponse('Service not found');
            }

            $this->serviceService->delete($service);

            return $this->successResponse(null, 'Service deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete service: '.$e->getMessage());
        }
    }
}
