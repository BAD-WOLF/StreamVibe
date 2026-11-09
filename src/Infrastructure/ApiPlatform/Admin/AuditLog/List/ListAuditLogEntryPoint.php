<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\AuditLog\List;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Admin\AuditLog\List\Model\ListAuditLogOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Admin\AuditLog\List\ListAuditLogController;

/**
 * GET /{_locale}/api/admin/audit-log — o log de auditoria, 20 entradas por
 * página, da mais nova para a mais antiga (ROLE_ADMIN).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ListAuditLogController, e o corpo de sucesso é o
 * ListAuditLogOutput.
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
                uriTemplate: '/admin/audit-log',
                status: 200,
                controller: ListAuditLogController::class,
                openapi: new Operation(
                    operationId: 'AdminListAuditLog',
                    tags: ['Admin'],
                    responses: [
                        200 => new Response(
                            description: 'OK — a page of entries and the pagination',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid page, user_id or action',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Action: Action must be one of: user_verified, verification_resent, user_blocked, user_unblocked, role_changed, sessions_revoked, user_deleted'],
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
                    ],
                    summary: 'List the audit log',
                    description: 'What was done to which account, by whom and when: 20 entries per page, newest first. actor null = done through the console (server access).',
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
                            name: 'user_id',
                            in: 'query',
                            description: 'Only the entries about this account.',
                            required: false,
                            schema: ParameterSchema::USER_ID,
                        ),
                        new Parameter(
                            name: 'action',
                            in: 'query',
                            description: 'Only the entries of this action.',
                            required: false,
                            schema: ['type' => 'string', 'enum' => ['user_verified', 'verification_resent', 'user_blocked', 'user_unblocked', 'role_changed', 'sessions_revoked', 'user_deleted']],
                        ),
                    ],
                ),
                output: ListAuditLogOutput::class,
                errors: [],
                name: 'get_admin_audit_log',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ListAuditLogEntryPoint {
}
