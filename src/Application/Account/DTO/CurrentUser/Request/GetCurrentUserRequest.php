<?php

declare(strict_types = 1);

namespace App\Application\Account\DTO\CurrentUser\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetCurrentUserUseCase (GET /me): o id do usuário do JWT.
 *
 * O id vem do controller (o usuário autenticado), nunca da URL: a rota não
 * tem parâmetro, então ninguém consegue pedir os dados de outra conta por
 * ela.
 *
 * NOTA (nov/2026): criado com o GET /me.
 */
final class GetCurrentUserRequest {
    /**
     * @param int $userId
     */
    public function __construct(
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
    ) {
    }
}
