<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\Model\CancelResetInput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\Model\CancelResetOutput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\Schema\TokenNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\BadRequestSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InternalServerErrorSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\CancelResetPasswordController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: "/reset-password/cancel",
                status: 200,
                controller: CancelResetPasswordController::class,
                openapi: new Operation(
                    operationId: "CancelResetPassword",
                    tags: ["Authentication"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid input or token format",
                            content: new BadRequestSchema(
                                "Invalid JSON data or missing token",
                            ),
                        ),
                        404 => new Response(
                            description: "Not Found — Reset token not found",
                            content: new TokenNotFoundSchema(),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while processing request",
                            content: new InternalServerErrorSchema(),
                        ),
                    ],
                    summary: "Cancel a password reset request",
                    description: "Cancels an active password reset request using its token",
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
                    ],
                ),
                input: CancelResetInput::class,
                output: CancelResetOutput::class,
                name: "post_cancel_reset_password",
            ),
        ],
    ),
]
class CancelResetEntryPoint
{
    // Empty class, because "Cancel Reset Password" is just a logical grouping.
// Its input and output are defined separately.
}
