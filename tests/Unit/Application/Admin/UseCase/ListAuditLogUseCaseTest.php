<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\AuditLog\List\Request\ListAuditLogRequest;
use App\Application\Admin\UseCase\AuditLog\List\ListAuditLogUseCase;
use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\UserValidationException;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use DateTimeImmutable;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * ListAuditLogUseCase: o actor e o target viram {id, email} (null quando a
 * entrada não tem: o console), os filtros chegam como tipos do Domain.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListAuditLogUseCaseTest extends AdminTestCase
{
    public function testEntriesAndFilters(): void
    {
        $byAnAdmin = new AuditLogEntry(AuditAction::UserBlocked, new DateTimeImmutable('2026-11-08 12:00:00'), 1, 'admin@example.com', 2, 'ana@example.com', ['role' => 'ROLE_USER', 'reason' => 'spam']);
        $byTheConsole = new AuditLogEntry(AuditAction::RoleChanged, new DateTimeImmutable('2026-11-08 11:00:00'), null, null, 2, 'ana@example.com', ['role' => 'ROLE_OWNER', 'previous_role' => 'ROLE_USER']);

        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $repository->expects($this->once())
                   ->method('findPage')
                   ->with(1, 20, 2, AuditAction::UserBlocked)
                   ->willReturn(['entries' => [$byAnAdmin, $byTheConsole], 'total' => 2]);

        $response = new ListAuditLogUseCase($repository, $this->validator(), new IdentityTranslator())
            ->execute(new ListAuditLogRequest(page: 1, userId: 2, action: 'user_blocked'));

        self::assertCount(2, $response->entries);
        [$first, $second] = $response->entries;
        self::assertSame('user_blocked', $first->action);
        self::assertSame('2026-11-08 12:00:00', $first->occurredAt->format('Y-m-d H:i:s'));
        self::assertSame([1, 'admin@example.com'], [$first->actor?->id, $first->actor?->email]);
        self::assertSame([2, 'ana@example.com'], [$first->target?->id, $first->target?->email]);
        self::assertSame(['role' => 'ROLE_USER', 'reason' => 'spam'], $first->details);
        self::assertNull($second->actor);
        self::assertSame([1, 1, 2], [$response->page, $response->totalPages, $response->totalResults]);
        self::assertSame('Audit log retrieved successfully', $response->message);
    }

    public function testAnUnknownActionIsRejected(): void
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $repository->expects($this->never())->method('findPage');

        $this->expectException(UserValidationException::class);

        new ListAuditLogUseCase($repository, $this->validator(), new IdentityTranslator())
            ->execute(new ListAuditLogRequest(action: 'user_hacked'));
    }
}
