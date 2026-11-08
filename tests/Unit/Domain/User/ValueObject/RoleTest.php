<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\ValueObject;

use App\Domain\User\ValueObject\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Role: a hierarquia ROLE_USER < ROLE_DEV < ROLE_ADMIN < ROLE_OWNER, cada
 * papel com tudo o do anterior. O mesmo desenho do role_hierarchy do
 * security.yaml (o RoleHierarchyApiTest confere os dois juntos).
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
final class RoleTest extends TestCase
{
    public function testTheOrderOfTheHierarchy(): void
    {
        self::assertSame(
            ['ROLE_USER', 'ROLE_DEV', 'ROLE_ADMIN', 'ROLE_OWNER'],
            Role::values(),
        );
        self::assertSame([0, 1, 2, 3], array_map(static fn(Role $role): int => $role->level(), Role::cases()));
    }

    public function testIncludesAndOutranks(): void
    {
        self::assertTrue(Role::Admin->includes(Role::Admin));
        self::assertTrue(Role::Admin->includes(Role::Dev));
        self::assertFalse(Role::Admin->includes(Role::Owner));

        self::assertTrue(Role::Admin->outranks(Role::Dev));
        self::assertFalse(Role::Admin->outranks(Role::Admin));
        self::assertFalse(Role::User->outranks(Role::Dev));
    }

    /**
     * @return iterable<string, array{\App\Domain\User\ValueObject\Role, list<string>}>
     */
    public static function inheritedProvider(): iterable
    {
        yield 'user' => [Role::User, ['ROLE_USER']];
        yield 'dev' => [Role::Dev, ['ROLE_DEV', 'ROLE_USER']];
        yield 'admin' => [Role::Admin, ['ROLE_ADMIN', 'ROLE_DEV', 'ROLE_USER']];
        yield 'owner' => [Role::Owner, ['ROLE_OWNER', 'ROLE_ADMIN', 'ROLE_DEV', 'ROLE_USER']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('inheritedProvider')]
    public function testEachRoleHasEverythingOfTheOnesBelow(Role $role, array $expected): void
    {
        self::assertSame($expected, array_map(static fn(Role $inherited): string => $inherited->value, $role->withInherited()));
    }

    /**
     * @return iterable<string, array{list<string>, \App\Domain\User\ValueObject\Role}>
     */
    public static function highestProvider(): iterable
    {
        yield 'nothing stored' => [[], Role::User];
        yield 'only the default' => [['ROLE_USER'], Role::User];
        yield 'one role' => [['ROLE_DEV', 'ROLE_USER'], Role::Dev];
        yield 'several, in any order' => [['ROLE_DEV', 'ROLE_OWNER', 'ROLE_ADMIN'], Role::Owner];
        yield 'names outside the hierarchy are ignored' => [['ROLE_SUPER_ADMIN', 'ROLE_DEV'], Role::Dev];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('highestProvider')]
    public function testHighestOf(array $roles, Role $expected): void
    {
        self::assertSame($expected, Role::highestOf($roles));
    }
}
