<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\UserRequests;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Authentication\ResetPassword\UserRequests\Model\GetUserPasswordResetRequestsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\UserRequests\GetUserPasswordResetRequestsController;

/**
 * GET /{_locale}/api/reset-password/requests/{userId} — as solicitações
 * de troca de senha ainda válidas de um usuário (exige JWT; só o próprio
 * usuário ou ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetUserPasswordResetRequestsController, e o corpo de sucesso
 * é o GetUserPasswordResetRequestsOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/ResetPassword/UserRequests/UserResetRequestsEntryPoint;
 *   renomeado para espelhar o GetUserPasswordResetRequestsUseCase.
 * - Entraram o 401 (a rota exige JWT) e o 403 (outro usuário — antes a
 *   rota respondia para qualquer usuário logado, ver o UseCase). Erros no
 *   formato real do ExceptionListener, no lugar de InvalidUserIdSchema,
 *   NotFoundSchema e InternalServerErrorSchema.
 * - Requirement numérico via RouteRequirement::INTEGER.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/reset-password/requests/{userId}',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetUserPasswordResetRequestsController::class,
                openapi: new Operation(
                    operationId: 'GetUserResetRequests',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — password reset requests of the user that did not expire yet (newest first)',
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
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the requests belong to another user (only the user and ROLE_ADMIN can list them)',
                            content: new ErrorResponseSchema(
                                type: 'permission_denied_error',
                                message: 'You can only access your own password reset requests',
                            ),
                        ),
                        404 => new Response(
                            description: 'Not Found — no user with this id',
                            content: new ErrorResponseSchema(
                                type: 'user_not_found_error',
                                message: 'User with ID 42 not found',
                            ),
                        ),
                    ],
                    summary: 'List the active password reset requests of a user',
                    description: 'Lists the password reset requests of the user that did not expire yet. A user can list only their own requests; ROLE_ADMIN can list anyone\'s.',
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
                            description: 'Id of the user',
                            required: true,
                            schema: ParameterSchema::USER_ID,
                        ),
                    ],
                ),
                output: GetUserPasswordResetRequestsOutput::class,
                errors: [],
                name: 'get_user_reset_requests',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetUserPasswordResetRequestsEntryPoint {
}
