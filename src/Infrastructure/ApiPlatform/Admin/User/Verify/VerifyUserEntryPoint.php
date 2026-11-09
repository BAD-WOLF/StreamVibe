<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Verify;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\Verify\Model\VerifyUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Verify\VerifyUserController;

/**
 * POST /{_locale}/api/admin/users/{userId}/verify — confirma o email de uma
 * conta no lugar da pessoa (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o VerifyUserController, e o corpo de sucesso é o
 * VerifyUserOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 *
 * NOTA (nov/2026): o 403 de quem não é ROLE_ADMIN traz o motivo, como o
 * Symfony responde ("Access Denied. The user doesn't have ROLE_ADMIN.").
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/admin/users/{userId}/verify',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: VerifyUserController::class,
                openapi: new Operation(
                    operationId: 'AdminVerifyUser',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the email of the account is confirmed',
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
                            description: 'Forbidden — the user of the JWT is not ROLE_ADMIN (http_error "Access Denied. The user doesn\'t have ROLE_ADMIN."); or the account is your own, or its role is not below yours (permission_denied_error)',
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
                    ],
                    summary: 'Confirm the email of an account',
                    description: 'Confirms the email of the account in place of its owner, who can log in from then on. Only accounts below your role (an owner: any other account).',
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
                output: VerifyUserOutput::class,
                errors: [],
                name: 'post_admin_user_verify',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class VerifyUserEntryPoint {
}
