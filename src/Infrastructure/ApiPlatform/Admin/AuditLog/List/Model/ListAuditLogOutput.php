<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\AuditLog\List\Model;

use App\Application\Admin\DTO\AuditLog\List\Response\ListAuditLogResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/admin/audit-log:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "entries": [{"id": 7, "action": "user_blocked", "occurred_at": "2026-11-08T12:00:00+00:00",
 *                      "actor": {"id": 1, "email": "owner@example.com"}, "target": {"id": 42, "email": "ana@example.com"},
 *                      "details": {"role": "ROLE_USER", "reason": "spam", "revoked_sessions": 2}}],
 *         "current_page": 1, "total_pages": 1, "total_results": 1,
 *         "has_next": false, "has_previous": false, "next_page": null, "previous_page": null
 *       },
 *       "message": "Audit log retrieved successfully"
 *     }
 *
 * Mapeado do ListAuditLogResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ListAuditLogResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(ListAuditLogResponse::class)]
final readonly class ListAuditLogOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\AuditLog\List\Response\ListAuditLogResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ListAuditLogResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
