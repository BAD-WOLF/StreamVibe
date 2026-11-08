<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Delete;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\Delete\Model\DeleteUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\SuccessResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Admin\User\Delete\DeleteUserController;

/**
 * DELETE /{_locale}/api/admin/users/{userId} — exclui uma conta (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o DeleteUserController, e o corpo de sucesso é o
 * DeleteUserOutput.
 *
 * Por ser DELETE, o corpo de sucesso é documentado à mão
 * (SuccessResponseSchema): o API Platform não o liga ao `output:` neste
 * método.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Delete(
                uriTemplate: '/admin/users/{userId}',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: DeleteUserController::class,
                openapi: new Operation(
                    operationId: 'AdminDeleteUser',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account was deleted',
                            content: new SuccessResponseSchema(
                                data: [
                                    'user_id' => ['type' => 'integer', 'example' => 42],
                                    'revoked_sessions' => ['type' => 'integer', 'example' => 2],
                                ],
                                message: 'User deleted successfully',
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
                            description: 'Conflict — the account is the last active owner',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'The last owner cannot lose the owner role, be blocked or be deleted',
                            ),
                        ),
                    ],
                    summary: 'Delete an account',
                    description: 'Deletes the account, its password reset requests and its sessions. The audit log keeps its entries. Only accounts below your role (an owner: any other account); the last active owner cannot be deleted.',
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
                output: DeleteUserOutput::class,
                errors: [],
                name: 'delete_admin_user',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class DeleteUserEntryPoint {
}
