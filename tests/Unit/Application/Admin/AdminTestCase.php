<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin;

use App\Application\Admin\Port\UserSessionsInterface;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validation;

/**
 * Base dos testes dos UseCases da administração: contas de teste, um
 * repositório de contas que registra o que foi gravado, um log de
 * auditoria e sessões em memória. As regras (AccountAdministration,
 * UserAdministrationPolicy, AuditTrail e a validação) são as de verdade.
 *
 * $this->events guarda a ordem das gravações: "audit:<ação>:flush|no-flush",
 * "save:<id>", "remove:<id>" e "revoke:<id>".
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
abstract class AdminTestCase extends TestCase
{
    /**
     * @var list<string>
     */
    protected array $events = [];

    /**
     * @var list<\App\Domain\Audit\Entity\AuditLogEntry>
     */
    protected array $entries = [];

    protected int $activeOwners = 1;

    protected function user(int $id, Role $role, bool $verified = true, bool $blocked = false): User
    {
        $user = new User();
        $user->setEmail("user{$id}@example.com")->setPassword('hashed-password')->setIsVerified($verified);
        $user->assignRole($role);
        $user->id = $id;

        if ($blocked) {
            $user->block('old reason', new DateTimeImmutable('2026-11-01 10:00:00'));
        }

        return $user;
    }

    /**
     * Um repositório de contas com $users: findById() as encontra, save() e
     * remove() vão para $this->events, e remove() zera o id como o Doctrine.
     */
    protected function users(User ...$users): UserRepositoryInterface&Stub
    {
        $byId = [];
        foreach ($users as $user) {
            $byId[(int) $user->getId()] = $user;
        }

        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findById')->willReturnCallback(static fn(int $id): ?User => $byId[$id] ?? null);
        $repository->method('countActiveWithRole')->willReturnCallback(fn(Role $role): int => $role === Role::Owner ? $this->activeOwners : 0);
        $repository->method('save')->willReturnCallback(function (User $user): void {
            $this->events[] = 'save:' . $user->getId();
        });
        $repository->method('remove')->willReturnCallback(function (User $user): void {
            $this->events[] = 'remove:' . $user->getId();
            $user->id = null;
        });

        return $repository;
    }

    protected function accounts(UserRepositoryInterface $users): AccountAdministration
    {
        return new AccountAdministration($users, new UserAdministrationPolicy());
    }

    protected function auditTrail(): AuditTrail
    {
        $auditLog = new class($this) implements AuditLogRepositoryInterface {
            public function __construct(private AdminTestCase $test)
            {
            }

            public function save(AuditLogEntry $entry, bool $flush = true): void
            {
                $this->test->recordAudit($entry, $flush);
            }

            public function findPage(int $page, int $perPage, ?int $targetUserId, ?AuditAction $action): array
            {
                return ['entries' => [], 'total' => 0];
            }
        };

        return new AuditTrail($auditLog);
    }

    /**
     * Chamado pelo log de auditoria em memória.
     */
    public function recordAudit(AuditLogEntry $entry, bool $flush): void
    {
        $this->entries[] = $entry;
        $this->recordEvent('audit:' . $entry->action->value . ':' . ($flush ? 'flush' : 'no-flush'));
    }

    /**
     * Chamado pelas sessões em memória.
     */
    public function recordEvent(string $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Sessões em memória: $active ainda valem; revokeAllFor() vai para
     * $this->events e devolve quantas eram.
     */
    protected function sessions(int $active): UserSessionsInterface
    {
        return new class($this, $active) implements UserSessionsInterface {
            public function __construct(private AdminTestCase $test, private int $active)
            {
            }

            public function countActiveFor(User $user): int
            {
                return $this->active;
            }

            public function revokeAllFor(User $user): int
            {
                $this->test->recordEvent('revoke:' . $user->getId());
                $revoked = $this->active;
                $this->active = 0;

                return $revoked;
            }
        };
    }

    protected function validator(): RequestValidator
    {
        return new RequestValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new NullLogger(),
        );
    }

    /**
     * A única entrada gravada no log.
     */
    protected function onlyEntry(): AuditLogEntry
    {
        self::assertCount(1, $this->entries);

        return $this->entries[0];
    }
}
