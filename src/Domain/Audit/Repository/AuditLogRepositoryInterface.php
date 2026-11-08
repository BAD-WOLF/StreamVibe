<?php

declare(strict_types = 1);

namespace App\Domain\Audit\Repository;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\ValueObject\AuditAction;

/**
 * O log de auditoria: só grava e lê (nenhuma entrada é alterada ou
 * apagada).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
interface AuditLogRepositoryInterface {
    /**
     * Com $flush = false, a entrada entra no banco junto com a próxima
     * gravação (a da conta afetada), na mesma transação: a ação e o
     * registro dela acontecem juntos ou não acontecem.
     *
     * @param \App\Domain\Audit\Entity\AuditLogEntry $entry
     * @param bool                                   $flush
     *
     * @return void
     */
    public function save(AuditLogEntry $entry, bool $flush = true): void;

    /**
     * Uma página do log, da entrada mais nova para a mais antiga.
     *
     * @param int                                            $page         a partir de 1
     * @param int                                            $perPage
     * @param int|null                                       $targetUserId só as entradas dessa conta
     * @param \App\Domain\Audit\ValueObject\AuditAction|null $action       só as entradas dessa ação
     *
     * @return array{entries: list<\App\Domain\Audit\Entity\AuditLogEntry>, total: int}
     */
    public function findPage(int $page, int $perPage, ?int $targetUserId, ?AuditAction $action): array;
}
