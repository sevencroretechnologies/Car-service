<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizationRequest;
use App\Services\OrganizationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class OrganizationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    /**
     * @OA\Get(
     *     path="/organizations",
     *     summary="List all organizations",
     *     description="Get paginated list of all organizations (Admin only)",
     *     operationId="organizationsIndex",
     *     tags={"Organizations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(response=200, description="Organizations retrieved successfully", @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $organizations = $this->organizationService->getAll($perPage);

            return $this->paginatedResponse($organizations, 'Organizations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve organizations: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/organizations",
     *     summary="Create organization",
     *     description="Create a new organization (Admin only)",
     *     operationId="organizationsStore",
     *     tags={"Organizations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="ABC Car Wash"),
     *             @OA\Property(property="email", type="string", format="email", example="info@abccarwash.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Organization created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(OrganizationRequest $request): JsonResponse
    {
        try {
            $organization = $this->organizationService->create($request->validated());

            return $this->createdResponse($organization, 'Organization created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create organization: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/organizations/{id}",
     *     summary="Get organization",
     *     description="Get organization details by ID (Admin only)",
     *     operationId="organizationsShow",
     *     tags={"Organizations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Organization ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Organization retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Organization not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $organization = $this->organizationService->findById($id);

            if (! $organization) {
                return $this->notFoundResponse('Organization not found');
            }

            $organization->load(['branches', 'users']);

            return $this->successResponse($organization, 'Organization retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve organization: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/organizations/{id}",
     *     summary="Update organization",
     *     description="Update organization details (Admin only)",
     *     operationId="organizationsUpdate",
     *     tags={"Organizations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Organization ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="ABC Car Wash Updated"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Organization updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Organization not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(OrganizationRequest $request, int $id): JsonResponse
    {
        try {
            $organization = $this->organizationService->findById($id);

            if (! $organization) {
                return $this->notFoundResponse('Organization not found');
            }

            $organization = $this->organizationService->update($organization, $request->validated());

            return $this->successResponse($organization, 'Organization updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update organization: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/organizations/{id}",
     *     summary="Delete organization",
     *     description="Soft delete an organization (Admin only)",
     *     operationId="organizationsDestroy",
     *     tags={"Organizations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Organization ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Organization deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Organization not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $organization = $this->organizationService->findById($id);

            if (! $organization) {
                return $this->notFoundResponse('Organization not found');
            }

            $this->organizationService->delete($organization);

            return $this->successResponse(null, 'Organization deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete organization: '.$e->getMessage());
        }
    }
}
