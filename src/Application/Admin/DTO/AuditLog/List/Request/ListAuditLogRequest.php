<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\AuditLog\List\Request;

use App\Domain\Audit\ValueObject\AuditAction;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ListAuditLogUseCase (GET /admin/audit-log): a página e os
 * filtros da query string (null = sem aquele filtro).
 *
 * - userId (?user_id=): só as entradas dessa conta (a afetada);
 * - action: só as entradas dessa ação, um dos valores de AuditAction.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListAuditLogRequest {
    /**
     * @param int         $page
     * @param int|null    $userId
     * @param string|null $action
     */
    public function __construct(
        #[Assert\Positive(message: 'Page must be a positive integer')]
        public private(set) int $page = 1 {
            /**
             * @return int
             */ get => $this->page;
        },
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) ?int $userId = null {
            /**
             * @return int|null
             */ get => $this->userId;
        },
        #[Assert\Choice(callback: [AuditAction::class, 'values'], message: 'Action must be one of: user_verified, verification_resent, user_blocked, user_unblocked, role_changed, sessions_revoked, user_deleted')]
        public private(set) ?string $action = null {
            /**
             * @return string|null
             */ get => $this->action;
        },
    ) {
    }
}
