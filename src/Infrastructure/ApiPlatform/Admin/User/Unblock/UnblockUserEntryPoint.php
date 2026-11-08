<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Unblock;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\Unblock\Model\UnblockUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Unblock\UnblockUserController;

/**
 * POST /{_locale}/api/admin/users/{userId}/unblock — libera uma conta
 * bloqueada (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o UnblockUserController, e o corpo de sucesso é o
 * UnblockUserOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/admin/users/{userId}/unblock',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: UnblockUserController::class,
                openapi: new Operation(
                    operationId: 'AdminUnblockUser',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account is not blocked anymore',
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
                            description: 'Conflict — the account is not blocked',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'This account is not blocked',
                            ),
                        ),
                    ],
                    summary: 'Unblock an account',
                    description: 'The account can log in again. The reason of the block leaves the account and stays in the audit log. Only accounts below your role (an owner: any other account).',
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
                output: UnblockUserOutput::class,
                errors: [],
                name: 'post_admin_user_unblock',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class UnblockUserEntryPoint {
}
