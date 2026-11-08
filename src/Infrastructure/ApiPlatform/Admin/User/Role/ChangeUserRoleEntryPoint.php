<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Role;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest;
use App\Infrastructure\ApiPlatform\Admin\User\Role\Model\ChangeUserRoleOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Role\ChangeUserRoleController;

/**
 * POST /{_locale}/api/admin/users/{userId}/role — muda o papel de uma conta
 * (ROLE_ADMIN). Corpo: {"role": "ROLE_DEV"}.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ChangeUserRoleController, e o corpo de sucesso é o
 * ChangeUserRoleOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/admin/users/{userId}/role',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: ChangeUserRoleController::class,
                openapi: new Operation(
                    operationId: 'AdminChangeUserRole',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account has the new role',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid userId, a role that does not exist, or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Role: Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired, or the account is blocked',
                            content: new UnauthorizedSchema(),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the user of the JWT is not ROLE_ADMIN (http_error "Access Denied."); or the account is your own, its role is not below yours, or the new role is not below yours (permission_denied_error)',
                            content: new ErrorResponseSchema(
                                type: 'permission_denied_error',
                                message: 'You cannot give the role ROLE_ADMIN',
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
                            description: 'Conflict — the account already has this role, or it is the last active owner',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'This account already has this role',
                            ),
                        ),
                    ],
                    summary: 'Change the role of an account',
                    description: 'Roles, each with everything of the one before: ROLE_USER < ROLE_DEV < ROLE_ADMIN < ROLE_OWNER. An admin changes accounts below its role and gives roles below its own (up to ROLE_DEV); an owner changes any other account and gives any role. The last active owner keeps the role. The new role counts from the next request of the account.',
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
                input: ChangeUserRoleRequest::class,
                output: ChangeUserRoleOutput::class,
                errors: [],
                name: 'post_admin_user_role',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ChangeUserRoleEntryPoint {
}
