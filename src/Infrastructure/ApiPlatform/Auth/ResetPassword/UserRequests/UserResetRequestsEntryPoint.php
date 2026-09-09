<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\UserRequests;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\UserRequests\Model\UserResetRequestsOutput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\UserRequests\Schema\InvalidUserIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\NotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InternalServerErrorSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\GetUserResetRequestsController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/reset-password/requests/{userId}",
                status: 200,
                controller: GetUserResetRequestsController::class,
                openapi: new Operation(
                    operationId: "GetUserResetRequests",
                    tags: ["Authentication"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid user ID format",
                            content: new InvalidUserIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — User not found",
                            content: new NotFoundSchema(
                                "User not found with the provided ID",
                            ),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while processing request",
                            content: new InternalServerErrorSchema(),
                        ),
                    ],
                    summary: "Get user password reset requests",
                    description: "Retrieves all active password reset requests for a specific user",
                    parameters: [
                        new Parameter(
                            name: "_locale",
                            in: "path",
                            required: true,
                            schema: [
                                "type" => "string",
                                "default" => "pt_BR",
                            ],
                        ),
                        new Parameter(
                            name: "userId",
                            in: "path",
                            description: "The ID of the user to retrieve reset requests for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: UserResetRequestsOutput::class,
                name: "get_user_reset_requests",
            ),
        ],
    ),
]
class UserResetRequestsEntryPoint
{
    // Empty class, because "User Reset Requests" is just a logical grouping.
// Its input and output are defined separately.
}
