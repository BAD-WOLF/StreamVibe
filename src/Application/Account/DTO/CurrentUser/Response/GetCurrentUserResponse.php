<?php

declare(strict_types = 1);

namespace App\Application\Account\DTO\CurrentUser\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetCurrentUserUseCase: o `data` de GET /me.
 *
 *     {"id": 42, "email": "ana@example.com", "roles": ["ROLE_USER"], "verified": true}
 *
 * O recurso é o próprio usuário, então o id é `id` (como o `id` de
 * GET /movies/{movieId}); `user_id` fica para as respostas que só
 * apontam para um usuário. `roles` são as do usuário, sempre com
 * ROLE_USER (User::getRoles()) — as mesmas que vão no JWT.
 *
 * NOTA (nov/2026): criado com o GET /me. O front não sabia o id da própria
 * conta: o JWT não o traz, e ele guardava o user_id do cadastro feito
 * naquele navegador.
 */
final class GetCurrentUserResponse {
    /**
     * @param int          $id
     * @param string       $email
     * @param list<string> $roles
     * @param bool         $verified
     * @param string|null  $message
     */
    public function __construct(
        public private(set) int $id {
            /**
             * @return int
             */ get => $this->id;
        },
        public private(set) string $email {
            /**
             * @return string
             */ get => $this->email;
        },
        public private(set) array $roles {
            /**
             * @return list<string>
             */ get => $this->roles;
        },
        public private(set) bool $verified {
            /**
             * @return bool
             */ get => $this->verified;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int          $id
     * @param string       $email
     * @param list<string> $roles
     * @param bool         $verified
     * @param string|null  $message
     *
     * @return self
     */
    public static function success(
        int $id,
        string $email,
        array $roles,
        bool $verified,
        ?string $message = null,
    ): self {
        return new self(id: $id, email: $email, roles: $roles, verified: $verified, message: $message);
    }
}
