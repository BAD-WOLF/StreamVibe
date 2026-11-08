<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Role\Request;

use App\Domain\User\ValueObject\Role;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ChangeUserRoleUseCase (POST /admin/users/{userId}/role, corpo
 * {"role": "ROLE_DEV"}, e o comando app:user:role).
 *
 * O id da conta vem da URL; o do admin (actorId), do usuário do JWT —
 * nunca do corpo. Quem pode administrar qual conta é regra do
 * UserAdministrationPolicy, aplicada no UseCase.
 *
 * É também o corpo documentado no Swagger (input da EntryPoint): userId e
 * actorId ficam fora dele com #[Ignore] — não vêm do corpo.
 *
 * actorId null = o console (quem tem acesso ao servidor): sem a regra de
 * quem administra quem, para dar o primeiro ROLE_OWNER. A do último owner
 * continua valendo. Pela API o actorId sempre vem do JWT.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ChangeUserRoleRequest {
    /**
     * @param int      $userId  a conta (URL)
     * @param int|null $actorId o admin (usuário do JWT); null = o console
     * @param string   $role    o papel novo (Role)
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
        public private(set) ?int $actorId {
            /**
             * @return int|null
             */ get => $this->actorId;
        },
        #[Assert\NotBlank(message: 'Role cannot be blank')]
        #[Assert\Choice(callback: [Role::class, 'values'], message: 'Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER')]
        public private(set) string $role {
            /**
             * @return string
             */ get => $this->role;
        },
    ) {
    }
}
