<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResendVerification;

use App\Application\Authentication\DTO\ResendVerification\Request\ResendVerificationEmailRequest;
use App\Application\Authentication\DTO\ResendVerification\Response\ResendVerificationEmailResponse;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Reenvia o email de confirmação de uma conta ainda não verificada.
 *
 * - conta não verificada: manda o email (template de reenvio) —
 *   email_sent true;
 * - nenhuma conta com o email: 200 com a mensagem genérica e
 *   email_sent false (mesmo comportamento do pedido de troca de senha);
 * - conta já verificada: 409.
 *
 * NOTA (out/2026): UseCase próprio. Antes havia dois reenvios:
 * - POST /{_locale}/api/resend-verification, que chamava o
 *   RegisterUserUseCase com a flag resendVerification (e respondia 404
 *   para email desconhecido);
 * - POST /api/verify/email/resend (EmailVerificationController), que NÃO
 *   mandava email nenhum: gerava o link assinado e o devolvia na resposta
 *   HTTP — qualquer pessoa conseguia o link de verificação de qualquer
 *   conta não verificada e a "verificava" sem ter acesso ao email.
 * Ficou só o primeiro endereço, com este UseCase.
 */
final readonly class ResendVerificationEmailUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface             $userRepository
     * @param \App\Application\Authentication\Port\EmailVerificationInterface $emailVerification
     * @param \App\Application\Shared\RequestValidator                        $requestValidator
     * @param \Psr\Log\LoggerInterface                                        $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface              $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailVerificationInterface $emailVerification,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResendVerification\Request\ResendVerificationEmailRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResendVerification\Response\ResendVerificationEmailResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserAlreadyExistsException
     * @throws \App\Domain\Exception\EmailDeliveryException
     */
    public function execute(
        ResendVerificationEmailRequest $request,
    ): ResendVerificationEmailResponse {
        $this->requestValidator->validate($request, 'resend_verification_request');

        $user = $this->userRepository->findByEmail($request->email);

        if ($user === null) {
            $this->logger->info('Verification resend requested for an unknown email');

            return ResendVerificationEmailResponse::success(
                emailSent: false,
                message: $this->translator->trans(
                    'If an account with this email exists and is not verified, a verification email has been sent.',
                ),
            );
        }

        if ($user->isVerified()) {
            throw new UserAlreadyExistsException(
                $this->translator->trans('This account is already verified'),
            );
        }

        $this->emailVerification->sendConfirmationEmail($user, isResend: true);

        $this->logger->info('Verification email resent', ['user_id' => $user->getId()]);

        return ResendVerificationEmailResponse::success(
            emailSent: true,
            message: $this->translator->trans(
                'Verification email has been sent. Please check your inbox.',
            ),
        );
    }
}
