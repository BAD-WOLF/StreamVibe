<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus\Model\CheckResetStatusOutput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus\Schema\InvalidTokenSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\NotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\GoneSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InternalServerErrorSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\CheckResetStatusController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/reset-password/status/{token}",
                status: 200,
                controller: CheckResetStatusController::class,
                openapi: new Operation(
                    operationId: "CheckResetPasswordStatus",
                    tags: ["Authentication"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid token format or missing token",
                            content: new InvalidTokenSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Reset token not found",
                            content: new NotFoundSchema(
                                "Reset token not found",
                            ),
                        ),
                        410 => new Response(
                            description: "Gone — Token has expired",
                            content: new GoneSchema("Token has expired"),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while checking token status",
                            content: new InternalServerErrorSchema(),
                        ),
                    ],
                    summary: "Check password reset token status",
                    description: "Verifies whether a password reset token is valid, expired, used, or invalid.",
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
                            name: "token",
                            in: "path",
                            description: "The reset password token to check",
                            required: true,
                            schema: [
                                "type" => "string",
                            ],
                        ),
                    ],
                ),
                output: CheckResetStatusOutput::class,
                name: "get_reset_password_status",
            ),
        ],
    ),
]
class CheckStatusEntryPoint
{
    // Empty class, because "Check Reset Password Status" is just a logical grouping.
// Its input and output are defined separately.
}
