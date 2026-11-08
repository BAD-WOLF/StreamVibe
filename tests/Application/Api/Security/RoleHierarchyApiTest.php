<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Security;

use App\Domain\User\ValueObject\Role;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * O role_hierarchy do config/packages/security.yaml (o que o isGranted() e
 * o access_control usam) é o mesmo do App\Domain\User\ValueObject\Role (o
 * que a administração de contas usa). Se um dos dois mudar sozinho, este
 * teste quebra.
 *
 * A hierarquia vem do container compilado (security.role_hierarchy.roles)
 * e é percorrida pelo RoleHierarchy do próprio Symfony.
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
final class RoleHierarchyApiTest extends KernelTestCase
{
    public function testTheSecurityHierarchyMirrorsTheRoleEnum(): void
    {
        $hierarchy = new RoleHierarchy($this->configuredHierarchy());

        foreach (Role::cases() as $role) {
            $expected = array_map(static fn(Role $inherited): string => $inherited->value, $role->withInherited());
            $reachable = $hierarchy->getReachableRoleNames([$role->value]);

            sort($expected);
            sort($reachable);
            $this->assertSame($expected, $reachable, "{$role->value} reaches different roles in security.yaml");
        }
    }

    public function testTheHierarchyHasNoOtherRoles(): void
    {
        $configured = $this->configuredHierarchy();
        $roles = array_values(array_unique(array_merge(array_keys($configured), ...array_values($configured))));
        sort($roles);

        $expected = Role::values();
        sort($expected);

        $this->assertSame($expected, $roles);
    }

    /**
     * @return array<string, list<string>>
     */
    private function configuredHierarchy(): array
    {
        self::bootKernel();

        /** @var array<string, list<string>> $configured */
        $configured = static::getContainer()->getParameter('security.role_hierarchy.roles');

        return $configured;
    }
}
