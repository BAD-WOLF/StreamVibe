<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Port;

use App\Domain\User\Entity\User;
use DateTimeImmutable;

/**
 * Resultado de PasswordResetInterface::validateToken(): o token é válido,
 * é deste usuário e vale até expiresAt.
 *
 * NOTA (out/2026): criado na divisão do antigo ResetUserPasswordUseCase.
 * O checkStatus() de lá validava o token pelo bundle e depois buscava a
 * solicitação com findByUser() — a MAIS RECENTE do usuário, que podia não
 * ser a do token conferido. O adaptador agora busca a solicitação do
 * próprio token.
 */
final readonly class ValidatedPasswordResetToken {
    /**
     * @param \App\Domain\User\Entity\User $user
     * @param \DateTimeImmutable           $expiresAt
     */
    public function __construct(
        public User $user,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
