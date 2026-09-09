<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\ResetPasswordSolicitationController;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Model\SolicitationInput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Model\SolicitationOutput;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema\ValidationErrorSchema;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema\ForbiddenSchema;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema\TooManyRequestsSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\BadRequestSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InternalServerErrorSchema;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: "/reset-password",
                status: 200,
                controller: ResetPasswordSolicitationController::class,
                openapi: new Operation(
                    operationId: "ResetPasswordSolicitation",
                    tags: ["Authentication"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid input or email format",
                            content: new BadRequestSchema(
                                "Invalid JSON data or email format",
                            ),
                        ),
                        403 => new Response(
                            description: "Forbidden — User account not verified",
                            content: new ForbiddenSchema(),
                        ),
                        422 => new Response(
                            description: "Unprocessable Entity — Validation failed",
                            content: new ValidationErrorSchema(),
                        ),
                        429 => new Response(
                            description: "Too Many Requests — Rate limit exceeded",
                            content: new TooManyRequestsSchema(),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while processing request",
                            content: new InternalServerErrorSchema(),
                        ),
                    ],
                    summary: "Request a password reset link",
                    description: "Handles user password reset request and email dispatch",
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
                input: SolicitationInput::class,
                output: SolicitationOutput::class,
                name: "post_reset_password_solicitation",
            ),
        ],
    ),
]
class SolicitationEntryPoint
{
    // Empty class, because "Reset Password Solicitation" is just a logical grouping.
// Its input and output are defined separately.
}
