<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Car Service Management API",
 *     description="API documentation for Car Service / Car Wash Management System",
 *
 *     @OA\Contact(
 *         email="admin@carservice.com",
 *         name="API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * @OA\Tag(
 *     name="Organizations",
 *     description="Organization management endpoints (Admin only)"
 * )
 * @OA\Tag(
 *     name="Branches",
 *     description="Branch management endpoints"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="User/Staff management endpoints"
 * )
 * @OA\Tag(
 *     name="Vehicle Types",
 *     description="Vehicle type management endpoints"
 * )
 * @OA\Tag(
 *     name="Vehicle Brands",
 *     description="Vehicle brand management endpoints"
 * )
 * @OA\Tag(
 *     name="Vehicle Models",
 *     description="Vehicle model management endpoints"
 * )
 * @OA\Tag(
 *     name="Services",
 *     description="Service management endpoints"
 * )
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management endpoints"
 * )
 * @OA\Tag(
 *     name="Customer Vehicles",
 *     description="Customer vehicle management endpoints"
 * )
 * @OA\Tag(
 *     name="Pricing",
 *     description="Vehicle service pricing management endpoints"
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="first_page_url", type="string"),
 *         @OA\Property(property="from", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="last_page_url", type="string"),
 *         @OA\Property(property="next_page_url", type="string", nullable=true),
 *         @OA\Property(property="path", type="string"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="prev_page_url", type="string", nullable=true),
 *         @OA\Property(property="to", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *     )
 * )
 */
class SwaggerInfo {}
