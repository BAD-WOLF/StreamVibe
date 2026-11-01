<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Port;

use App\Domain\User\Entity\User;

/**
 * Porta da troca de senha por email: gerar, conferir e invalidar o token
 * e mandar o email com o link. A implementação
 * (Infrastructure\Security\PasswordResetManager) usa o
 * ResetPasswordBundle e o Mailer.
 *
 * O token é o que vai no link do email ("selector + verifier", 40
 * caracteres); no banco fica só o hash (ResetPasswordSolicitation).
 *
 * NOTA (out/2026): criada na divisão do antigo ResetUserPasswordUseCase,
 * que usava o ResetPasswordHelperInterface (bundle), o Mailer e o
 * TemplatedEmail direto e traduzia as exceções do bundle em cada método.
 * As traduções agora ficam no adaptador, uma vez só.
 */
interface PasswordResetInterface {
    /**
     * Cria uma solicitação de troca de senha e devolve o token.
     *
     * @param \App\Domain\User\Entity\User $user
     *
     * @return \App\Application\Authentication\Port\PasswordResetToken
     * @throws \App\Domain\Exception\RateLimitExceededException se o usuário pediu outra troca há pouco
     */
    public function generateToken(User $user): PasswordResetToken;

    /**
     * Manda o email com o link de troca de senha.
     *
     * @param \App\Domain\User\Entity\User                            $user
     * @param \App\Application\Authentication\Port\PasswordResetToken $token
     *
     * @return void
     * @throws \App\Domain\Exception\EmailDeliveryException se o email não puder ser enviado
     */
    public function sendResetEmail(User $user, PasswordResetToken $token): void;

    /**
     * Confere o token e devolve o dono e a validade.
     *
     * @param string $token
     *
     * @return \App\Application\Authentication\Port\ValidatedPasswordResetToken
     * @throws \App\Domain\Exception\InvalidResetTokenException token mal formado ou inexistente
     * @throws \App\Domain\Exception\ExpiredResetTokenException token vencido
     */
    public function validateToken(string $token): ValidatedPasswordResetToken;

    /**
     * Invalida o token (apaga a solicitação).
     *
     * @param string $token
     *
     * @return void
     * @throws \App\Domain\Exception\ResetTokenNotFoundException se não houver solicitação com esse token
     */
    public function removeToken(string $token): void;
}
