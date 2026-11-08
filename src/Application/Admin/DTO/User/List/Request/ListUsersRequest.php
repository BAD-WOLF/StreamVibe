<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\List\Request;

use App\Domain\User\ValueObject\Role;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ListUsersUseCase (GET /admin/users): a página e os filtros
 * da query string (null = sem aquele filtro).
 *
 * - search: trecho do email (sem diferenciar maiúsculas) ou, só com
 *   dígitos, também o id; vazio = sem filtro;
 * - role: o papel da conta (o mais alto), um dos valores de Role;
 * - verified / blocked: true ou false.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListUsersRequest {
    /**
     * @param int         $page
     * @param string|null $search
     * @param string|null $role
     * @param bool|null   $verified
     * @param bool|null   $blocked
     */
    public function __construct(
        #[Assert\Positive(message: 'Page must be a positive integer')]
        public private(set) int $page = 1 {
            /**
             * @return int
             */ get => $this->page;
        },
        #[Assert\Length(max: 180, maxMessage: 'Search cannot be longer than {{ limit }} characters')]
        public private(set) ?string $search = null {
            /**
             * @return string|null
             */ get => $this->search;
        },
        #[Assert\Choice(callback: [Role::class, 'values'], message: 'Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER')]
        public private(set) ?string $role = null {
            /**
             * @return string|null
             */ get => $this->role;
        },
        public private(set) ?bool $verified = null {
            /**
             * @return bool|null
             */ get => $this->verified;
        },
        public private(set) ?bool $blocked = null {
            /**
             * @return bool|null
             */ get => $this->blocked;
        },
    ) {
    }
}
