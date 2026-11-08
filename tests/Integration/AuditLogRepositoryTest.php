<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Infrastructure\Persistence\Repository\AuditLogRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * AuditLogRepository contra o banco de verdade: a ordem (a mais nova
 * primeiro), os filtros, a paginação e os campos de volta como foram
 * gravados (ação como enum, detalhes em JSON, actor null).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class AuditLogRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private AuditLogRepositoryInterface $auditLogRepository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->auditLogRepository = $this->entityManager->getRepository(AuditLogEntry::class);
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testFindPage(): void
    {
        $this->assertInstanceOf(AuditLogRepository::class, $this->auditLogRepository);

        $this->save(AuditAction::UserBlocked, actorId: 1, targetUserId: 10, details: ['role' => 'ROLE_USER', 'reason' => 'spam', 'revoked_sessions' => 2]);
        $this->save(AuditAction::RoleChanged, actorId: null, targetUserId: 11, details: ['role' => 'ROLE_OWNER', 'previous_role' => 'ROLE_USER']);
        $this->save(AuditAction::UserUnblocked, actorId: 1, targetUserId: 10, details: ['role' => 'ROLE_USER', 'previous_reason' => 'spam']);
        $this->entityManager->clear();

        $actions = static fn(array $page): array => array_map(static fn(AuditLogEntry $entry): string => $entry->action->value, $page['entries']);

        $all = $this->auditLogRepository->findPage(1, 20, null, null);
        $this->assertSame(['user_unblocked', 'role_changed', 'user_blocked'], $actions($all));
        $this->assertSame(3, $all['total']);

        $this->assertSame(['user_unblocked', 'user_blocked'], $actions($this->auditLogRepository->findPage(1, 20, 10, null)));
        $this->assertSame(['user_blocked'], $actions($this->auditLogRepository->findPage(1, 20, 10, AuditAction::UserBlocked)));
        $this->assertSame([[], 0], [$this->auditLogRepository->findPage(1, 20, 99, null)['entries'], 0]);

        $second = $this->auditLogRepository->findPage(2, 1, null, null);
        $this->assertSame([['role_changed'], 3], [$actions($second), $second['total']]);

        $byTheConsole = $second['entries'][0];
        $this->assertSame(AuditAction::RoleChanged, $byTheConsole->action);
        $this->assertNull($byTheConsole->actorId);
        $this->assertNull($byTheConsole->actorEmail);
        $this->assertSame(['role' => 'ROLE_OWNER', 'previous_role' => 'ROLE_USER'], $byTheConsole->details);
        $this->assertSame('user11@example.com', $byTheConsole->targetEmail);
    }

    /**
     * @param array<string, string|int|bool|null> $details
     */
    private function save(AuditAction $action, ?int $actorId, int $targetUserId, array $details): void
    {
        $this->auditLogRepository->save(new AuditLogEntry(
            action: $action,
            occurredAt: new DateTimeImmutable(),
            actorId: $actorId,
            actorEmail: $actorId === null ? null : "user{$actorId}@example.com",
            targetUserId: $targetUserId,
            targetEmail: "user{$targetUserId}@example.com",
            details: $details,
        ));
    }

    private function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM ' . AuditLogEntry::class . ' entry')->execute();
        $this->entityManager->clear();
    }
}
