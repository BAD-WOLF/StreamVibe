<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\List;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\User\List\Model\ListUsersOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Admin\User\List\ListUsersController;

/**
 * GET /{_locale}/api/admin/users — as contas, 20 por página, da mais nova
 * para a mais antiga, com filtros (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ListUsersController, e o corpo de sucesso é o
 * ListUsersOutput.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/admin/users',
                status: 200,
                controller: ListUsersController::class,
                openapi: new Operation(
                    operationId: 'AdminListUsers',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — a page of accounts (users) and the pagination',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid page, search, role, verified or blocked',
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
                            description: 'Forbidden — the user of the JWT is not ROLE_ADMIN (http_error "Access Denied.")',
                            content: new ErrorResponseSchema(
                                type: 'http_error',
                                message: 'Access Denied.',
                            ),
                        ),
                    ],
                    summary: 'List the accounts',
                    description: 'Accounts, 20 per page, newest first, with optional filters. Every ROLE_ADMIN sees every account, including the ones it cannot change.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'page',
                            in: 'query',
                            description: 'Result page (from 1).',
                            required: false,
                            schema: ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                        ),
                        new Parameter(
                            name: 'search',
                            in: 'query',
                            description: 'Part of the email (case-insensitive) or, with digits only, also the exact id.',
                            required: false,
                            schema: ['type' => 'string', 'maxLength' => 180],
                        ),
                        new Parameter(
                            name: 'role',
                            in: 'query',
                            description: 'Only accounts with this role (their highest one).',
                            required: false,
                            schema: ['type' => 'string', 'enum' => ['ROLE_USER', 'ROLE_DEV', 'ROLE_ADMIN', 'ROLE_OWNER']],
                        ),
                        new Parameter(
                            name: 'verified',
                            in: 'query',
                            description: 'Only accounts with (true) or without (false) a confirmed email.',
                            required: false,
                            schema: ['type' => 'boolean'],
                        ),
                        new Parameter(
                            name: 'blocked',
                            in: 'query',
                            description: 'Only blocked (true) or not blocked (false) accounts.',
                            required: false,
                            schema: ['type' => 'boolean'],
                        ),
                    ],
                ),
                output: ListUsersOutput::class,
                errors: [],
                name: 'get_admin_users',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ListUsersEntryPoint {
}
