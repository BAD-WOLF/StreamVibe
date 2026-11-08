<?php

declare(strict_types = 1);

namespace App\Domain\Audit\Entity;

use App\Domain\Audit\ValueObject\AuditAction;
use DateTimeImmutable;

/**
 * Uma entrada do log de auditoria: quem fez o quê, em qual conta e quando.
 *
 * Quem fez (actor) e a conta afetada (target) ficam como id e email, sem
 * chave estrangeira: a entrada continua legível depois que qualquer uma
 * das contas for excluída — inclusive a exclusão em si. actor null = feito
 * pelo console (acesso ao servidor). `details` guarda o que mudou (papel
 * anterior e novo, motivo do bloqueio, sessões revogadas...).
 *
 * Nunca é alterada nem apagada pela aplicação.
 *
 * NOTA (nov/2026): criada com a administração de contas. Mapeamento em
 * src/Infrastructure/Persistence/Doctrine/Mapping/Audit/AuditLogEntry.orm.xml.
 */
class AuditLogEntry {
    public private(set) ?int $id = null {
        get => $this->id;
    }

    /**
     * @param \App\Domain\Audit\ValueObject\AuditAction $action
     * @param \DateTimeImmutable                        $occurredAt
     * @param int|null                                  $actorId
     * @param string|null                               $actorEmail
     * @param int|null                                  $targetUserId
     * @param string|null                               $targetEmail
     * @param array<string, scalar|null>                $details
     */
    public function __construct(
        public private(set) AuditAction $action {
            get => $this->action;
        },
        public private(set) DateTimeImmutable $occurredAt {
            get => $this->occurredAt;
        },
        public private(set) ?int $actorId {
            get => $this->actorId;
        },
        public private(set) ?string $actorEmail {
            get => $this->actorEmail;
        },
        public private(set) ?int $targetUserId {
            get => $this->targetUserId;
        },
        public private(set) ?string $targetEmail {
            get => $this->targetEmail;
        },
        public private(set) array $details = [] {
            get => $this->details;
        },
    ) {
    }
}
