<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VehicleTypeRequest;
use App\Services\VehicleTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class VehicleTypeController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected VehicleTypeService $vehicleTypeService
    ) {}

    /**
     * @OA\Get(
     *     path="/vehicle-types",
     *     summary="List vehicle types",
     *     description="Get paginated list of vehicle types",
     *     operationId="vehicleTypesIndex",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(response=200, description="Vehicle types retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $vehicleTypes = $this->vehicleTypeService->getAll($perPage);

            return $this->paginatedResponse($vehicleTypes, 'Vehicle types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vehicle types: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/vehicle-types/list",
     *     summary="List all vehicle types",
     *     description="Get all vehicle types without pagination",
     *     operationId="vehicleTypesList",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Vehicle types retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function list(): JsonResponse
    {
        try {
            $vehicleTypes = $this->vehicleTypeService->getAllWithoutPagination();

            return $this->successResponse($vehicleTypes, 'Vehicle types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vehicle types: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/vehicle-types",
     *     summary="Create vehicle type",
     *     description="Create a new vehicle type",
     *     operationId="vehicleTypesStore",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *
     *         @OA\Property(property="name", type="string", example="Sedan"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Vehicle type created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(VehicleTypeRequest $request): JsonResponse
    {
        try {
            $vehicleType = $this->vehicleTypeService->create($request->validated());

            return $this->createdResponse($vehicleType, 'Vehicle type created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create vehicle type: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/vehicle-types/{id}",
     *     summary="Get vehicle type",
     *     description="Get vehicle type details by ID",
     *     operationId="vehicleTypesShow",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Vehicle Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Vehicle type retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Vehicle type not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $vehicleType = $this->vehicleTypeService->findById($id);

            if (! $vehicleType) {
                return $this->notFoundResponse('Vehicle type not found');
            }

            $vehicleType->load('vehicleBrands');

            return $this->successResponse($vehicleType, 'Vehicle type retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vehicle type: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/vehicle-types/{id}",
     *     summary="Update vehicle type",
     *     description="Update vehicle type details",
     *     operationId="vehicleTypesUpdate",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Vehicle Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="Vehicle type updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Vehicle type not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(VehicleTypeRequest $request, int $id): JsonResponse
    {
        try {
            $vehicleType = $this->vehicleTypeService->findById($id);

            if (! $vehicleType) {
                return $this->notFoundResponse('Vehicle type not found');
            }

            $vehicleType = $this->vehicleTypeService->update($vehicleType, $request->validated());

            return $this->successResponse($vehicleType, 'Vehicle type updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update vehicle type: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/vehicle-types/{id}",
     *     summary="Delete vehicle type",
     *     description="Soft delete a vehicle type",
     *     operationId="vehicleTypesDestroy",
     *     tags={"Vehicle Types"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Vehicle Type ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Vehicle type deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Vehicle type not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $vehicleType = $this->vehicleTypeService->findById($id);

            if (! $vehicleType) {
                return $this->notFoundResponse('Vehicle type not found');
            }

            $this->vehicleTypeService->delete($vehicleType);

            return $this->successResponse(null, 'Vehicle type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete vehicle type: '.$e->getMessage());
        }
    }
}
