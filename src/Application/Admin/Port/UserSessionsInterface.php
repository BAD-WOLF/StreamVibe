<?php

declare(strict_types = 1);

namespace App\Application\Admin\Port;

use App\Domain\User\Entity\User;

/**
 * Porta para as sessões de uma conta. Uma sessão é um refresh token (o
 * cookie HttpOnly de cada navegador em que a pessoa entrou); a
 * implementação (Infrastructure\Security\RefreshTokenSessions) lê e apaga
 * as linhas da tabela refresh_tokens do gesdinet.
 *
 * Encerrar as sessões não derruba na hora o access token que cada
 * navegador já tem (é stateless, vale até 15 minutos): o que impede uma
 * conta bloqueada de continuar é o UserCheckerApi, que confere o bloqueio
 * a cada requisição.
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
interface UserSessionsInterface {
    /**
     * Quantas sessões da conta ainda valem (refresh tokens não vencidos).
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return int
     */
    public function countActiveFor(User $user): int;

    /**
     * Encerra todas as sessões da conta.
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return int quantas ainda valiam (as vencidas são apagadas junto, sem contar)
     */
    public function revokeAllFor(User $user): int;
}
