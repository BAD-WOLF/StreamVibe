<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Port;

use App\Domain\User\Entity\User;

/**
 * Porta para invalidar os refresh tokens (JWT) de um usuário. A
 * implementação (Infrastructure\Security\RefreshTokenRevoker) apaga as
 * linhas da tabela refresh_tokens do gesdinet.
 *
 * Usada depois da troca de senha: quem estava logado com a senha antiga
 * (inclusive alguém com a conta invadida) não consegue mais renovar o
 * JWT. Os access tokens já emitidos continuam válidos até expirar — são
 * stateless.
 *
 * NOTA (out/2026): criada junto com POST /reset-password/confirm.
 */
interface RefreshTokenRevokerInterface {
    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return int quantos refresh tokens foram invalidados
     */
    public function revokeAllFor(User $user): int;
}
