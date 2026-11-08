<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\ResendVerification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\ResendVerification\Model\ResendUserVerificationOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\ResendVerification\ResendUserVerificationController;

/**
 * POST /{_locale}/api/admin/users/{userId}/resend-verification — manda de
 * novo o email de confirmação de uma conta (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ResendUserVerificationController, e o corpo de sucesso é o
 * ResendUserVerificationOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/admin/users/{userId}/resend-verification',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: ResendUserVerificationController::class,
                openapi: new Operation(
                    operationId: 'AdminResendVerification',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the email was sent',
                        ),
                        400 => new Response(
                            description: 'Bad Request — userId is not a positive integer',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['User_id: User ID must be a positive integer'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired, or the account is blocked',
                            content: new UnauthorizedSchema(),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the user of the JWT is not ROLE_ADMIN (http_error "Access Denied."); or the account is your own, or its role is not below yours (permission_denied_error)',
                            content: new ErrorResponseSchema(
                                type: 'permission_denied_error',
                                message: 'Only a role above ROLE_ADMIN can manage this account',
                            ),
                        ),
                        404 => new Response(
                            description: 'Not Found — no account with this id',
                            content: new ErrorResponseSchema(
                                type: 'user_not_found_error',
                                message: 'User with ID 42 not found',
                            ),
                        ),
                        409 => new Response(
                            description: 'Conflict — the account is already verified',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'This account is already verified',
                            ),
                        ),
                        503 => new Response(
                            description: 'Service Unavailable — the email could not be sent',
                            content: new ErrorResponseSchema(
                                type: 'email_delivery_error',
                                message: 'Email delivery failed. Please try again later or contact support.',
                            ),
                        ),
                    ],
                    summary: 'Send the email confirmation again',
                    description: 'Sends again to the account the email with the confirmation link (the same email of POST /resend-verification). Only accounts below your role (an owner: any other account).',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'userId',
                            in: 'path',
                            description: 'Id of the account',
                            required: true,
                            schema: ParameterSchema::USER_ID,
                        ),
                    ],
                ),
                input: false,
                output: ResendUserVerificationOutput::class,
                errors: [],
                name: 'post_admin_user_resend_verification',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ResendUserVerificationEntryPoint {
}
