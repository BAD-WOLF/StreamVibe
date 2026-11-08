<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Block\Request;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do BlockUserUseCase (POST /admin/users/{userId}/block, corpo
 * {"reason": "..."}).
 *
 * O id da conta vem da URL; o do admin (actorId), do usuário do JWT —
 * nunca do corpo. Quem pode administrar qual conta é regra do
 * UserAdministrationPolicy, aplicada no UseCase.
 *
 * É também o corpo documentado no Swagger (input da EntryPoint): userId e
 * actorId ficam fora dele com #[Ignore] — não vêm do corpo.
 *
 * O motivo é obrigatório: fica na conta (blocked_reason) e no log de
 * auditoria, e é o que outro admin lê depois para decidir se desbloqueia.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class BlockUserRequest {
    /**
     * @param int    $userId  a conta (URL)
     * @param int    $actorId o admin (usuário do JWT)
     * @param string $reason  por que a conta foi bloqueada
     */
    public function __construct(
        #[Ignore]
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[Ignore]
        #[Assert\Positive(message: 'Actor ID must be a positive integer')]
        public private(set) int $actorId {
            /**
             * @return int
             */ get => $this->actorId;
        },
        #[Assert\NotBlank(message: 'Reason cannot be blank', normalizer: 'trim')]
        #[Assert\Length(max: 255, maxMessage: 'Reason cannot be longer than {{ limit }} characters')]
        public private(set) string $reason {
            /**
             * @return string
             */ get => $this->reason;
        },
    ) {
    }
}
