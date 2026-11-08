<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Service;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\User\Entity\User;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * UserAdministrationPolicy: quem administra qual conta e que papéis pode
 * dar; e o último owner ativo.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class UserAdministrationPolicyTest extends TestCase
{
    private UserAdministrationPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new UserAdministrationPolicy();
    }

    /**
     * @return iterable<string, array{\App\Domain\User\ValueObject\Role, \App\Domain\User\ValueObject\Role, bool}>
     */
    public static function manageProvider(): iterable
    {
        foreach (Role::cases() as $target) {
            yield "user -> {$target->name}" => [Role::User, $target, false];
            yield "dev -> {$target->name}" => [Role::Dev, $target, false];
        }

        yield 'admin -> user' => [Role::Admin, Role::User, true];
        yield 'admin -> dev' => [Role::Admin, Role::Dev, true];
        yield 'admin -> admin' => [Role::Admin, Role::Admin, false];
        yield 'admin -> owner' => [Role::Admin, Role::Owner, false];
        yield 'owner -> user' => [Role::Owner, Role::User, true];
        yield 'owner -> dev' => [Role::Owner, Role::Dev, true];
        yield 'owner -> admin' => [Role::Owner, Role::Admin, true];
        yield 'owner -> another owner' => [Role::Owner, Role::Owner, true];
    }

    #[DataProvider('manageProvider')]
    public function testWhoManagesWhom(Role $actor, Role $target, bool $expected): void
    {
        self::assertSame($expected, $this->policy->canManage($this->user(1, $actor), $this->user(2, $target)));
    }

    public function testNobodyManagesTheirOwnAccount(): void
    {
        $owner = $this->user(1, Role::Owner);

        self::assertFalse($this->policy->canManage($owner, $owner));
        self::assertFalse($this->policy->canManage($owner, $this->user(1, Role::Owner)), 'same id, other instance');
        self::assertSame([], $this->policy->assignableRoles($owner, $owner));

        try {
            $this->policy->assertCanManage($owner, $owner);
            self::fail('the own account should be refused');
        } catch (PermissionDeniedException $exception) {
            self::assertSame(403, $exception->getCode());
            self::assertSame('own_account', $exception->getContext()['rule']);
        }
    }

    public function testAnAccountThatIsNotBelowIsRefused(): void
    {
        try {
            $this->policy->assertCanManage($this->user(1, Role::Admin), $this->user(2, Role::Admin));
            self::fail('an admin should not manage another admin');
        } catch (PermissionDeniedException $exception) {
            self::assertSame(403, $exception->getCode());
            self::assertSame(['rule' => 'higher_role', 'target_role' => 'ROLE_ADMIN'], $exception->getContext());
        }
    }

    /**
     * @return iterable<string, array{\App\Domain\User\ValueObject\Role, \App\Domain\User\ValueObject\Role, bool}>
     */
    public static function assignProvider(): iterable
    {
        yield 'admin gives user' => [Role::Admin, Role::User, true];
        yield 'admin gives dev' => [Role::Admin, Role::Dev, true];
        yield 'admin gives admin' => [Role::Admin, Role::Admin, false];
        yield 'admin gives owner' => [Role::Admin, Role::Owner, false];
        yield 'owner gives admin' => [Role::Owner, Role::Admin, true];
        yield 'owner gives owner' => [Role::Owner, Role::Owner, true];
    }

    #[DataProvider('assignProvider')]
    public function testWhichRolesCanBeGiven(Role $actor, Role $role, bool $allowed): void
    {
        if (!$allowed) {
            $this->expectException(PermissionDeniedException::class);
            $this->expectExceptionMessage("You cannot give the role {$role->value}");
        }

        $this->policy->assertCanAssign($this->user(1, $actor), $role);

        $this->addToAssertionCount(1);
    }

    public function testAssignableRolesFollowTheSameRules(): void
    {
        $values = static fn(array $roles): array => array_map(static fn(Role $role): string => $role->value, $roles);

        self::assertSame(['ROLE_USER', 'ROLE_DEV'], $values($this->policy->assignableRoles($this->user(1, Role::Admin), $this->user(2, Role::User))));
        self::assertSame(Role::values(), $values($this->policy->assignableRoles($this->user(1, Role::Owner), $this->user(2, Role::Admin))));
        self::assertSame([], $this->policy->assignableRoles($this->user(1, Role::Admin), $this->user(2, Role::Owner)));
    }

    public function testTheLastActiveOwnerStays(): void
    {
        $owner = $this->user(2, Role::Owner);

        try {
            $this->policy->assertKeepsAnOwner($owner, activeOwners: 1);
            self::fail('the last active owner should stay');
        } catch (BusinessLogicException $exception) {
            self::assertSame(409, $exception->getCode());
            self::assertSame('last_owner', $exception->getContext()['rule']);
        }

        $this->policy->assertKeepsAnOwner($owner, activeOwners: 2);
        $this->policy->assertKeepsAnOwner($this->user(3, Role::Admin), activeOwners: 0);
        $this->policy->assertKeepsAnOwner($this->user(4, Role::Owner, blocked: true), activeOwners: 1);

        $this->addToAssertionCount(3);
    }

    private function user(int $id, Role $role, bool $blocked = false): User
    {
        $user = new User();
        $user->setEmail("user{$id}@example.com")->setPassword('hashed-password')->setIsVerified(true);
        $user->assignRole($role);
        $user->id = $id;

        if ($blocked) {
            $user->block('test', new DateTimeImmutable());
        }

        return $user;
    }
}
