<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Block;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Admin\DTO\User\Block\Request\BlockUserRequest;
use App\Infrastructure\ApiPlatform\Admin\User\Block\Model\BlockUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Block\BlockUserController;

/**
 * POST /{_locale}/api/admin/users/{userId}/block — bloqueia uma conta
 * (ROLE_ADMIN). Corpo: {"reason": "..."}.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o BlockUserController, e o corpo de sucesso é o
 * BlockUserOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/admin/users/{userId}/block',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: BlockUserController::class,
                openapi: new Operation(
                    operationId: 'AdminBlockUser',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account is blocked and its sessions ended',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid userId, a missing or too long reason, or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Reason: Reason cannot be blank'],
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
                            description: 'Conflict — the account is already blocked, or it is the last active owner',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'This account is already blocked',
                            ),
                        ),
                    ],
                    summary: 'Block an account',
                    description: 'Blocks the account: its login is refused, every request with an access token it already had too, and its sessions end. The reason stays on the account and in the audit log. Only accounts below your role (an owner: any other account); the last active owner cannot be blocked.',
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
                input: BlockUserRequest::class,
                output: BlockUserOutput::class,
                errors: [],
                name: 'post_admin_user_block',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class BlockUserEntryPoint {
}
