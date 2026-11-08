<?php

declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLogEntry>
 *
 * O log de auditoria (tabela audit_log): só grava e lê.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
class AuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface {
    /**
     * @param \Doctrine\Persistence\ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, AuditLogEntry::class);
    }

    /**
     * {@inheritDoc}
     */
    public function save(AuditLogEntry $entry, bool $flush = true): void {
        $this->getEntityManager()->persist($entry);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findPage(int $page, int $perPage, ?int $targetUserId, ?AuditAction $action): array {
        $filtered = $this->createQueryBuilder('entry');

        if ($targetUserId !== null) {
            $filtered->andWhere('entry.targetUserId = :targetUserId')
                     ->setParameter('targetUserId', $targetUserId);
        }

        if ($action !== null) {
            $filtered->andWhere('entry.action = :action')
                     ->setParameter('action', $action->value);
        }

        $total = (int) (clone $filtered)
            ->select('COUNT(entry.id)')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<\App\Domain\Audit\Entity\AuditLogEntry> $entries */
        $entries = $filtered
            ->orderBy('entry.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['entries' => $entries, 'total' => $total];
    }
}
