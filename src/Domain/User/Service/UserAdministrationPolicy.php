<?php

declare(strict_types = 1);

namespace App\Domain\User\Service;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;

/**
 * Quem pode administrar qual conta, e o que pode fazer com ela.
 *
 * - Ninguém administra a própria conta por aqui (não se bloqueia, não muda
 *   o próprio papel, não se exclui): evita que um admin se tranque para
 *   fora sem querer.
 * - Precisa ser ROLE_ADMIN ou acima, e estar acima do papel da conta. A
 *   exceção é o ROLE_OWNER, o topo: ele administra todas as outras contas,
 *   inclusive as de outros owners.
 * - Um papel só é dado por quem está acima dele (o admin dá até ROLE_DEV);
 *   o owner dá qualquer um, inclusive ROLE_OWNER.
 * - Sempre fica pelo menos um owner ativo (não bloqueado): o último não
 *   perde o papel, não é bloqueado nem excluído. Pela API isso já não
 *   acontece (só um owner mexe num owner, e não em si mesmo, então há
 *   dois); a regra vale para o console e como garantia.
 *
 * Ver qualquer conta (a lista e o detalhe) é de todo ROLE_ADMIN.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class UserAdministrationPolicy {
    /**
     * @param \App\Domain\User\Entity\User $actor
     * @param \App\Domain\User\Entity\User $target
     *
     * @return bool
     */
    public function canManage(User $actor, User $target): bool {
        if ($this->isSameAccount($actor, $target)) {
            return false;
        }

        $actorRole = $actor->getRole();

        if (!$actorRole->includes(Role::Admin)) {
            return false;
        }

        return $actorRole === Role::Owner || $actorRole->outranks($target->getRole());
    }

    /**
     * @param \App\Domain\User\Entity\User $actor
     * @param \App\Domain\User\Entity\User $target
     *
     * @return void
     * @throws \App\Domain\Exception\PermissionDeniedException
     */
    public function assertCanManage(User $actor, User $target): void {
        if ($this->isSameAccount($actor, $target)) {
            throw PermissionDeniedException::ownAccount();
        }

        if (!$this->canManage($actor, $target)) {
            throw PermissionDeniedException::higherRole($target->getRole()->value);
        }
    }

    /**
     * @param \App\Domain\User\Entity\User      $actor
     * @param \App\Domain\User\ValueObject\Role $role
     *
     * @return void
     * @throws \App\Domain\Exception\PermissionDeniedException
     */
    public function assertCanAssign(User $actor, Role $role): void {
        $actorRole = $actor->getRole();

        if ($actorRole !== Role::Owner && !$actorRole->outranks($role)) {
            throw PermissionDeniedException::roleAboveYours($role->value);
        }
    }

    /**
     * Os papéis que $actor pode dar a $target (nenhum quando não pode
     * administrar a conta), do mais baixo ao mais alto.
     *
     * @param \App\Domain\User\Entity\User $actor
     * @param \App\Domain\User\Entity\User $target
     *
     * @return list<\App\Domain\User\ValueObject\Role>
     */
    public function assignableRoles(User $actor, User $target): array {
        if (!$this->canManage($actor, $target)) {
            return [];
        }

        $actorRole = $actor->getRole();

        return array_values(array_filter(
            Role::cases(),
            static fn(Role $role): bool => $actorRole === Role::Owner || $actorRole->outranks($role),
        ));
    }

    /**
     * A conta deixa de ser um owner ativo (papel novo, bloqueio ou
     * exclusão): ainda precisa sobrar outro. Um owner já bloqueado não
     * conta — mexer nele não muda quantos owners ativos há.
     *
     * @param \App\Domain\User\Entity\User $target
     * @param int                          $activeOwners quantos owners ativos há hoje (com $target)
     *
     * @return void
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function assertKeepsAnOwner(User $target, int $activeOwners): void {
        if ($target->getRole() === Role::Owner && !$target->isBlocked() && $activeOwners <= 1) {
            throw BusinessLogicException::lastOwner();
        }
    }

    /**
     * @param \App\Domain\User\Entity\User $actor
     * @param \App\Domain\User\Entity\User $target
     *
     * @return bool
     */
    private function isSameAccount(User $actor, User $target): bool {
        return $actor === $target || ($actor->getId() !== null && $actor->getId() === $target->getId());
    }
}
