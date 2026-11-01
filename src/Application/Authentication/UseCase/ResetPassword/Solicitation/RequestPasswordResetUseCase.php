<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResetPassword\Solicitation;

use App\Application\Authentication\DTO\ResetPassword\Solicitation\Request\RequestPasswordResetRequest;
use App\Application\Authentication\DTO\ResetPassword\Solicitation\Response\RequestPasswordResetResponse;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Pedido de troca de senha (POST /reset-password): manda o link por
 * email.
 *
 * - nenhuma conta com o email: 200 com mensagem genérica, email_sent
 *   false;
 * - conta não verificada: 403 (UserNotVerifiedException);
 * - já existe um link válido: 200, email_sent false e a validade do link
 *   existente (não manda outro);
 * - link novo: 200, email_sent true;
 * - o email não sai: 503 — e o link recém-criado é apagado, senão o
 *   usuário ficaria preso no "já existe um link válido" sem ter recebido
 *   nada.
 *
 * NOTA (out/2026): era o execute() do ResetUserPasswordUseCase, que
 * também tinha checkStatus(), cancel() e getUserActiveRequests() — cada
 * um virou um UseCase (Solicitation, CheckStatus, Cancel, UserRequests),
 * e entrou o Confirm (definir a senha nova), que não existia.
 * - Depende da PasswordResetInterface (o bundle, o Mailer e o template
 *   ficaram no adaptador).
 * - Falha no envio do email agora é 503 (EmailDeliveryException). Antes
 *   o erro era engolido e a resposta era 200 com email_sent false — igual
 *   a "email desconhecido".
 * - Saíram o catch (Exception) que trocava tudo por RuntimeException
 *   (500), a checagem "3 ou mais solicitações" (inalcançável: o fluxo já
 *   tinha retornado com 1) e os ~20 logs de passo a passo.
 */
final readonly class RequestPasswordResetUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                                $userRepository
     * @param \App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface $resetRequestRepository
     * @param \App\Application\Authentication\Port\PasswordResetInterface                        $passwordReset
     * @param \App\Application\Shared\RequestValidator                                           $requestValidator
     * @param \Psr\Log\LoggerInterface                                                           $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface                                 $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
        private PasswordResetInterface $passwordReset,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Solicitation\Request\RequestPasswordResetRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResetPassword\Solicitation\Response\RequestPasswordResetResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotVerifiedException
     * @throws \App\Domain\Exception\RateLimitExceededException
     * @throws \App\Domain\Exception\EmailDeliveryException
     */
    public function execute(
        RequestPasswordResetRequest $request,
    ): RequestPasswordResetResponse {
        $this->requestValidator->validate($request, 'request_password_reset_request');

        $user = $this->userRepository->findByEmail($request->email);

        if ($user === null) {
            $this->logger->info('Password reset requested for an unknown email');

            return RequestPasswordResetResponse::success(
                emailSent: false,
                message: $this->translator->trans(
                    'If an account with this email exists, a password reset link has been sent.',
                ),
            );
        }

        if (!$user->isVerified()) {
            throw new UserNotVerifiedException(
                $this->translator->trans(
                    'Please verify your email address before requesting a password reset.',
                ),
            );
        }

        $this->resetRequestRepository->removeExpiredRequestsFor($user);

        $activeRequests = $this->resetRequestRepository->findActiveRequestsForUser($user);

        if ($activeRequests !== []) {
            $this->logger->info('Password reset already requested; no new email sent', [
                'user_id' => $user->getId(),
            ]);

            return RequestPasswordResetResponse::success(
                emailSent: false,
                expiresAt: DateTimeImmutable::createFromInterface($activeRequests[0]->getExpiresAt()),
                message: $this->translator->trans(
                    'A password reset link has already been sent to your email. Please check your inbox or wait a few minutes before requesting a new one.',
                ),
            );
        }

        $token = $this->passwordReset->generateToken($user);

        try {
            $this->passwordReset->sendResetEmail($user, $token);
        } catch (EmailDeliveryException $exception) {
            // Sem o email, o link novo só atrapalharia o próximo pedido.
            $this->passwordReset->removeToken($token->token);

            throw $exception;
        }

        $this->logger->info('Password reset link sent', ['user_id' => $user->getId()]);

        return RequestPasswordResetResponse::success(
            emailSent: true,
            expiresAt: $token->expiresAt,
            message: $this->translator->trans(
                'A password reset link has been sent to your email address.',
            ),
        );
    }
}
