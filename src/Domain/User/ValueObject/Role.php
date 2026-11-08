<?php

declare(strict_types = 1);

namespace App\Domain\User\ValueObject;

/**
 * Os papéis de uma conta, em hierarquia: cada um herda tudo do anterior.
 *
 * - ROLE_USER: toda conta (catálogo, a própria conta);
 * - ROLE_DEV: + o painel do sistema (processos, memória, logs);
 * - ROLE_ADMIN: + a administração das contas (verificar, bloquear,
 *   mudar papel, excluir) e o log de auditoria;
 * - ROLE_OWNER: + as configurações e as chaves da aplicação; é o único
 *   que mexe em outros admins e owners.
 *
 * A conta guarda só o papel dela (User::assignRole()); os herdados vêm
 * daqui. A mesma hierarquia está no role_hierarchy do
 * config/packages/security.yaml, que o Symfony usa no isGranted() e no
 * access_control — o RoleHierarchyApiTest confere que as duas batem.
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis e a administração
 * de contas.
 */
enum Role: string {
    case User = 'ROLE_USER';
    case Dev = 'ROLE_DEV';
    case Admin = 'ROLE_ADMIN';
    case Owner = 'ROLE_OWNER';

    /**
     * A posição na hierarquia (0 = ROLE_USER).
     *
     * @return int
     */
    public function level(): int {
        return match ($this) {
            self::User => 0,
            self::Dev => 1,
            self::Admin => 2,
            self::Owner => 3,
        };
    }

    /**
     * Este papel tem tudo o que $other tem (é igual ou acima dele).
     *
     * @param \App\Domain\User\ValueObject\Role $other
     *
     * @return bool
     */
    public function includes(self $other): bool {
        return $this->level() >= $other->level();
    }

    /**
     * Este papel está acima de $other.
     *
     * @param \App\Domain\User\ValueObject\Role $other
     *
     * @return bool
     */
    public function outranks(self $other): bool {
        return $this->level() > $other->level();
    }

    /**
     * Este papel e todos os que ele herda, do mais alto ao mais baixo.
     *
     * @return list<self>
     */
    public function withInherited(): array {
        $roles = array_filter(
            self::cases(),
            fn(self $role): bool => $this->includes($role),
        );
        usort($roles, static fn(self $a, self $b): int => $b->level() <=> $a->level());

        return $roles;
    }

    /**
     * O papel mais alto de uma lista de nomes (User::getRoles()); nomes que
     * não são papéis da hierarquia ficam de fora. Sem nenhum: ROLE_USER.
     *
     * @param array<array-key, string> $roles
     *
     * @return self
     */
    public static function highestOf(array $roles): self {
        $highest = self::User;

        foreach ($roles as $name) {
            $role = self::tryFrom($name);

            if ($role !== null && $role->outranks($highest)) {
                $highest = $role;
            }
        }

        return $highest;
    }

    /**
     * Os nomes dos papéis (para o Choice dos Request DTOs).
     *
     * @return list<string>
     */
    public static function values(): array {
        return array_map(static fn(self $role): string => $role->value, self::cases());
    }
}
