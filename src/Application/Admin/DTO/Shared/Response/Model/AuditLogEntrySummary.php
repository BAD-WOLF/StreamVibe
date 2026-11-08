<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\Shared\Response\Model;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Uma entrada do log de auditoria (`data.entries` de GET /admin/audit-log):
 *
 *     {"id": 7, "action": "user_blocked", "occurred_at": "2026-11-08T12:00:00+00:00",
 *      "actor": {"id": 1, "email": "owner@example.com"},
 *      "target": {"id": 42, "email": "ana@example.com"},
 *      "details": {"role": "ROLE_USER", "reason": "spam", "revoked_sessions": 2}}
 *
 * - action: um dos valores do App\Domain\Audit\ValueObject\AuditAction;
 * - actor: quem fez — null quando foi pelo console (acesso ao servidor);
 * - target: a conta afetada;
 * - details: o que mudou. Sempre traz `role`, o papel da conta depois da
 *   ação; o resto depende da ação (previous_role, reason,
 *   previous_reason, revoked_sessions).
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
final class AuditLogEntrySummary {
    /**
     * @param int                                                                 $id
     * @param string                                                              $action
     * @param \DateTimeImmutable                                                  $occurredAt
     * @param \App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount|null $actor
     * @param \App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount|null $target
     * @param array<string, string|int|bool|null>                                          $details
     */
    public function __construct(
        public private(set) int $id {
            /**
             * @return int
             */ get => $this->id;
        },
        public private(set) string $action {
            /**
             * @return string
             */ get => $this->action;
        },
        #[SerializedName('occurred_at')]
        public private(set) DateTimeImmutable $occurredAt {
            /**
             * @return \DateTimeImmutable
             */ get => $this->occurredAt;
        },
        public private(set) ?AuditLogAccount $actor {
            /**
             * @return \App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount|null
             */ get => $this->actor;
        },
        public private(set) ?AuditLogAccount $target {
            /**
             * @return \App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount|null
             */ get => $this->target;
        },
        public private(set) array $details {
            /**
             * @return array<string, string|int|bool|null>
             */ get => $this->details;
        },
    ) {
    }
}
