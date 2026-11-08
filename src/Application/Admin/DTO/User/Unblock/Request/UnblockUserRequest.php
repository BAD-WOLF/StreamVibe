<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Unblock\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do UnblockUserUseCase (POST /admin/users/{userId}/unblock).
 *
 * O id da conta vem da URL; o do admin (actorId), do usuário do JWT —
 * nunca do corpo. Quem pode administrar qual conta é regra do
 * UserAdministrationPolicy, aplicada no UseCase.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class UnblockUserRequest {
    /**
     * @param int $userId  a conta (URL)
     * @param int $actorId o admin (usuário do JWT)
     */
    public function __construct(
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[Assert\Positive(message: 'Actor ID must be a positive integer')]
        public private(set) int $actorId {
            /**
             * @return int
             */ get => $this->actorId;
        },
    ) {
    }
}
