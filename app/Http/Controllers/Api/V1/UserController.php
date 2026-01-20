<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserRequest;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="List users",
     *     description="Get paginated list of users (Admin/Branch Manager only)",
     *     operationId="usersIndex",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="branch_id", in="query", description="Filter by branch", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Users retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);
            $branchId = $request->input('branch_id');

            $users = $this->userService->getAll($user->org_id, $branchId, $perPage);

            return $this->paginatedResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve users: '.$e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Create user",
     *     description="Create a new staff user (Admin/Branch Manager only)",
     *     operationId="usersStore",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "name", "email", "password", "role"},
     *
     *         @OA\Property(property="org_id", type="integer", example=1),
     *         @OA\Property(property="branch_id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="John Doe"),
     *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *         @OA\Property(property="phone", type="string", example="+1234567890"),
     *         @OA\Property(property="password", type="string", format="password", example="password123"),
     *         @OA\Property(property="role", type="string", enum={"admin", "branch_manager", "staff"}, example="staff"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *
     *     @OA\Response(response=201, description="User created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $currentUser = $request->user();

            if ($data['org_id'] != $currentUser->org_id) {
                return $this->forbiddenResponse('You can only create users for your organization');
            }

            $user = $this->userService->create($data);

            return $this->createdResponse($user, 'User created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create user: '.$e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user",
     *     description="Get user details by ID",
     *     operationId="usersShow",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="User retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $user = $this->userService->findByIdAndOrganization($id, $currentUser->org_id);

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $user->load(['organization', 'branch']);

            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user: '.$e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update user",
     *     description="Update user details",
     *     operationId="usersUpdate",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"org_id", "name", "email", "role"},
     *
     *         @OA\Property(property="org_id", type="integer"),
     *         @OA\Property(property="branch_id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="password", type="string", format="password"),
     *         @OA\Property(property="role", type="string", enum={"admin", "branch_manager", "staff"}),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *
     *     @OA\Response(response=200, description="User updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $user = $this->userService->findByIdAndOrganization($id, $currentUser->org_id);

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $data = $request->validated();
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user = $this->userService->update($user, $data);

            return $this->successResponse($user, 'User updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update user: '.$e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete user",
     *     description="Soft delete a user",
     *     operationId="usersDestroy",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=400, description="Cannot delete own account"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $request->user();

            if ($currentUser->id === $id) {
                return $this->errorResponse('You cannot delete your own account', 400);
            }

            $user = $this->userService->findByIdAndOrganization($id, $currentUser->org_id);

            if (! $user) {
                return $this->notFoundResponse('User not found');
            }

            $this->userService->delete($user);

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete user: '.$e->getMessage());
        }
    }
}
