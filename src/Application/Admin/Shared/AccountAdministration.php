<?php

declare(strict_types = 1);

namespace App\Application\Admin\Shared;

use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;

/**
 * As contas de uma ação de administração: quem faz (o admin, usuário do
 * JWT) e a conta afetada, já com a regra de quem administra quem
 * (UserAdministrationPolicy).
 *
 * Ordem dos erros, igual em todas as ações: 404 (a conta não existe), 403
 * (a própria conta, ou um papel que não está abaixo do de quem pede) e só
 * então o estado da conta (409, no UseCase).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class AccountAdministration {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Domain\User\Service\UserAdministrationPolicy   $policy
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAdministrationPolicy $policy,
    ) {
    }

    /**
     * Quem está administrando: a conta do usuário do JWT.
     *
     * @param int $actorId
     *
     * @return \App\Domain\User\Entity\User
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function actor(int $actorId): User {
        return $this->account($actorId);
    }

    /**
     * Uma conta, para ler (a lista e o detalhe são de todo ROLE_ADMIN).
     *
     * @param int $userId
     *
     * @return \App\Domain\User\Entity\User
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function account(int $userId): User {
        return $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($userId);
    }

    /**
     * A conta que $actor vai alterar.
     *
     * @param \App\Domain\User\Entity\User $actor
     * @param int                          $userId
     *
     * @return \App\Domain\User\Entity\User
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     */
    public function managedAccount(User $actor, int $userId): User {
        $user = $this->account($userId);
        $this->policy->assertCanManage($actor, $user);

        return $user;
    }
}
