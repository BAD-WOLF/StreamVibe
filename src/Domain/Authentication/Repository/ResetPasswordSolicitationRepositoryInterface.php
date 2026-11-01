<?php
declare(strict_types = 1);

namespace App\Domain\Authentication\Repository;

use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\User\Entity\User;

/**
 * Repositório das solicitações de troca de senha (o que a aplicação usa).
 *
 * NOTA (out/2026): saíram daqui os métodos que só o ResetPasswordBundle
 * usa (createResetPasswordRequest(), persistResetPasswordRequest(),
 * findResetPasswordRequest(), getMostRecentNonExpiredRequestDate(),
 * removeResetPasswordRequest(), removeExpiredResetPasswordRequests()).
 * Eles continuam implementados no ResetPasswordSolicitationRepository,
 * que implementa também a interface do bundle — é por ela que o bundle os
 * chama. Repeti-los aqui fazia o Domain importar tipos do bundle
 * (ResetPasswordRequestInterface) sem nenhum uso na aplicação.
 */
interface ResetPasswordSolicitationRepositoryInterface {
    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function save(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void;

    /**
     * @param \App\Domain\Authentication\Entity\ResetPasswordSolicitation $resetPasswordRequest
     * @param bool                                                        $flush
     *
     * @return void
     */
    public function remove(
        ResetPasswordSolicitation $resetPasswordRequest,
        bool $flush = false,
    ): void;

    /**
     * A solicitação mais recente do usuário (expirada ou não).
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findByUser(User $user): ?ResetPasswordSolicitation;

    /**
     * @param string $selector
     *
     * @return \App\Domain\Authentication\Entity\ResetPasswordSolicitation|null
     */
    public function findBySelector(string $selector): ?ResetPasswordSolicitation;

    /**
     * Solicitações ainda válidas (não expiradas) do usuário, da mais
     * recente para a mais antiga.
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return list<\App\Domain\Authentication\Entity\ResetPasswordSolicitation>
     */
    public function findActiveRequestsForUser(User $user): array;

    /**
     * Remove as solicitações expiradas de todos os usuários.
     *
     * @return int quantas foram removidas
     */
    public function removeExpiredRequests(): int;

    /**
     * Remove só as solicitações expiradas deste usuário.
     *
     * NOTA (out/2026): substitui o laço do antigo
     * ResetUserPasswordUseCase::cleanupExpiredRequests(), que nunca
     * removia nada: ele percorria findActiveRequestsForUser() — que só
     * devolve solicitações NÃO expiradas — e removia as que estivessem
     * expiradas. A limpeza é por usuário de propósito: o garbage
     * collection do bundle está desligado
     * (config/packages/reset_password.yaml), e apagar as expiradas de
     * todo mundo faria um link vencido de outro usuário responder
     * "inválido" (400) em vez de "expirado" (410).
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return int quantas foram removidas
     */
    public function removeExpiredRequestsFor(User $user): int;
}
