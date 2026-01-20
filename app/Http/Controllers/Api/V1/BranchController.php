<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BranchRequest;
use App\Services\BranchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class BranchController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BranchService $branchService
    ) {}

    /**
     * @OA\Get(
     *     path="/branches",
     *     summary="List branches",
     *     description="Get paginated list of branches for current organization",
     *     operationId="branchesIndex",
     *     tags={"Branches"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(response=200, description="Branches retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);
            $branches = $this->branchService->getAll($user->org_id, $perPage);

            return $this->paginatedResponse($branches, 'Branches retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branches: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/branches",
     *     summary="Create branch",
     *     description="Create a new branch for the organization",
     *     operationId="branchesStore",
     *     tags={"Branches"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"org_id", "name"},
     *
     *             @OA\Property(property="org_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Downtown Branch"),
     *             @OA\Property(property="code", type="string", example="DT001"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Branch created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(BranchRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            if ($data['org_id'] != $user->org_id) {
                return $this->forbiddenResponse('You can only create branches for your organization');
            }

            $branch = $this->branchService->create($data);

            return $this->createdResponse($branch, 'Branch created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create branch: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/branches/{id}",
     *     summary="Get branch",
     *     description="Get branch details by ID",
     *     operationId="branchesShow",
     *     tags={"Branches"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Branch retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Branch not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $branch = $this->branchService->findByIdAndOrganization($id, $user->org_id);

            if (! $branch) {
                return $this->notFoundResponse('Branch not found');
            }

            $branch->load(['organization', 'users', 'services']);

            return $this->successResponse($branch, 'Branch retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve branch: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/branches/{id}",
     *     summary="Update branch",
     *     description="Update branch details",
     *     operationId="branchesUpdate",
     *     tags={"Branches"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "name"},
     *
     *         @OA\Property(property="org_id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="code", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="address", type="string"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="Branch updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Branch not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(BranchRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $branch = $this->branchService->findByIdAndOrganization($id, $user->org_id);

            if (! $branch) {
                return $this->notFoundResponse('Branch not found');
            }

            $branch = $this->branchService->update($branch, $request->validated());

            return $this->successResponse($branch, 'Branch updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update branch: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/branches/{id}",
     *     summary="Delete branch",
     *     description="Soft delete a branch",
     *     operationId="branchesDestroy",
     *     tags={"Branches"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Branch deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Branch not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $branch = $this->branchService->findByIdAndOrganization($id, $user->org_id);

            if (! $branch) {
                return $this->notFoundResponse('Branch not found');
            }

            $this->branchService->delete($branch);

            return $this->successResponse(null, 'Branch deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete branch: '.$e->getMessage());
        }
    }
}
