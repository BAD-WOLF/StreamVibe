<?php

declare(strict_types = 1);

namespace App\Application\Admin\Shared;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\User\Entity\User;
use DateTimeImmutable;

/**
 * Registra no log de auditoria uma ação sobre uma conta.
 *
 * $flush = false quando a ação também grava a conta (verificar, bloquear,
 * mudar papel, excluir): a entrada vai para o banco junto com a conta, na
 * mesma transação — a ação e o registro dela acontecem juntos ou não
 * acontecem. true quando a conta não muda (reenvio do email, sessões).
 *
 * `details` sempre leva `role`: o papel da conta depois da ação.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class AuditTrail {
    /**
     * @param \App\Domain\Audit\Repository\AuditLogRepositoryInterface $auditLogRepository
     */
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @param \App\Domain\Audit\ValueObject\AuditAction $action
     * @param \App\Domain\User\Entity\User|null         $actor   null = o console
     * @param \App\Domain\User\Entity\User              $target
     * @param array<string, scalar|null>                $details além do `role`
     * @param bool                                      $flush
     *
     * @return void
     */
    public function record(
        AuditAction $action,
        ?User $actor,
        User $target,
        array $details,
        bool $flush,
    ): void {
        $this->auditLogRepository->save(
            new AuditLogEntry(
                action: $action,
                occurredAt: new DateTimeImmutable(),
                actorId: $actor?->getId(),
                actorEmail: $actor?->getEmail(),
                targetUserId: $target->getId(),
                targetEmail: $target->getEmail(),
                details: ['role' => $target->getRole()->value] + $details,
            ),
            $flush,
        );
    }
}
