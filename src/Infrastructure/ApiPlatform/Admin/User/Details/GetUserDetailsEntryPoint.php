<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Details;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\Details\Model\GetUserDetailsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Details\GetUserDetailsController;

/**
 * GET /{_locale}/api/admin/users/{userId} — tudo o que a aplicação sabe de
 * uma conta (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetUserDetailsController, e o corpo de sucesso é o
 * GetUserDetailsOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 *
 * NOTA (nov/2026): o 403 de quem não é ROLE_ADMIN traz o motivo, como o
 * Symfony responde ("Access Denied. The user doesn't have ROLE_ADMIN.").
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/admin/users/{userId}',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetUserDetailsController::class,
                openapi: new Operation(
                    operationId: 'AdminGetUser',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account',
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
                            description: 'Forbidden — the user of the JWT is not ROLE_ADMIN (http_error "Access Denied. The user doesn\'t have ROLE_ADMIN.")',
                            content: new ErrorResponseSchema(
                                type: 'http_error',
                                message: 'Access Denied. The user doesn\'t have ROLE_ADMIN.',
                            ),
                        ),
                        404 => new Response(
                            description: 'Not Found — no account with this id',
                            content: new ErrorResponseSchema(
                                type: 'user_not_found_error',
                                message: 'User with ID 42 not found',
                            ),
                        ),
                    ],
                    summary: 'Get everything about an account',
                    description: 'The account, its active sessions, its password reset requests that did not expire, and what the user of the JWT can do with it (can_manage, assignable_roles). The history of the account is GET /admin/audit-log?user_id=.',
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
                output: GetUserDetailsOutput::class,
                errors: [],
                name: 'get_admin_user_details',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetUserDetailsEntryPoint {
}
