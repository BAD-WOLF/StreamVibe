<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Sessions;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\Sessions\Model\RevokeUserSessionsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\SuccessResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Sessions\RevokeUserSessionsController;

/**
 * DELETE /{_locale}/api/admin/users/{userId}/sessions — encerra as sessões
 * de uma conta em todos os navegadores (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o RevokeUserSessionsController, e o corpo de sucesso é o
 * RevokeUserSessionsOutput.
 *
 * Por ser DELETE, o corpo de sucesso é documentado à mão
 * (SuccessResponseSchema): o API Platform não o liga ao `output:` neste
 * método.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 *
 * NOTA (nov/2026): o 403 de quem não é ROLE_ADMIN traz o motivo, como o
 * Symfony responde ("Access Denied. The user doesn't have ROLE_ADMIN.").
 */
#[
    ApiResource(
        operations: [
            new Delete(
                uriTemplate: '/admin/users/{userId}/sessions',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: RevokeUserSessionsController::class,
                openapi: new Operation(
                    operationId: 'AdminRevokeUserSessions',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — how many active sessions ended',
                            content: new SuccessResponseSchema(
                                data: [
                                    'user_id' => ['type' => 'integer', 'example' => 42],
                                    'revoked_sessions' => ['type' => 'integer', 'example' => 2],
                                ],
                                message: 'User sessions revoked successfully',
                            ),
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
                    ],
                    summary: 'End the sessions of an account',
                    description: 'Ends the sessions of the account in every browser: each one needs a new login when its access token expires (up to 15 minutes). Only accounts below your role (an owner: any other account).',
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
                output: RevokeUserSessionsOutput::class,
                errors: [],
                name: 'delete_admin_user_sessions',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class RevokeUserSessionsEntryPoint {
}
