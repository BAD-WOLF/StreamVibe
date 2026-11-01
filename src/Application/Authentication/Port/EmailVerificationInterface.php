<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Port;

use App\Domain\User\Entity\User;

/**
 * Porta da verificação de email: mandar o email com o link assinado e
 * conferir o link quando o usuário clica nele. A implementação
 * (Infrastructure\Security\EmailVerifier) usa o VerifyEmailBundle e o
 * Mailer.
 *
 * NOTA (out/2026): antes os UseCases e o EmailVerificationController
 * dependiam do EmailVerifier concreto (Infrastructure) e montavam o
 * TemplatedEmail eles mesmos; o RegisterUserUseCase conhecia template,
 * remetente e nome de rota. Agora isso fica no adaptador, e o Application
 * só pede "mande a confirmação" e "confira este link".
 */
interface EmailVerificationInterface {
    /**
     * Gera o link assinado e manda o email de confirmação.
     *
     * @param \App\Domain\User\Entity\User $user
     * @param bool                         $isResend true no reenvio (usa o template de reenvio)
     *
     * @return void
     * @throws \App\Domain\Exception\EmailDeliveryException se o email não puder ser enviado
     */
    public function sendConfirmationEmail(User $user, bool $isResend = false): void;

    /**
     * Confere o link assinado (assinatura, validade e se é deste usuário).
     *
     * @param string                       $signedUrl a URL completa que o usuário abriu
     * @param \App\Domain\User\Entity\User $user
     *
     * @return void
     * @throws \App\Domain\Exception\InvalidVerificationLinkException link inválido, expirado ou de outro usuário
     */
    public function validateLink(string $signedUrl, User $user): void;
}
