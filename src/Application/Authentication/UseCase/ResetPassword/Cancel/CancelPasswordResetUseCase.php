<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResetPassword\Cancel;

use App\Application\Authentication\DTO\ResetPassword\Cancel\Request\CancelPasswordResetRequest;
use App\Application\Authentication\DTO\ResetPassword\Cancel\Response\CancelPasswordResetResponse;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\ResetTokenNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Cancela um pedido de troca de senha (POST /reset-password/cancel): o
 * link deixa de valer. Token inválido, inexistente ou vencido responde
 * 404 — não há pedido válido para cancelar.
 *
 * NOTA (out/2026): era ResetUserPasswordUseCase::cancel(). Continua
 * conferindo o token inteiro antes de apagar (só o "selector", os 20
 * primeiros caracteres, bastaria para o bundle achar o pedido).
 */
final readonly class CancelPasswordResetUseCase {
    /**
     * @param \App\Application\Authentication\Port\PasswordResetInterface $passwordReset
     * @param \App\Application\Shared\RequestValidator                    $requestValidator
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     */
    public function __construct(
        private PasswordResetInterface $passwordReset,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Cancel\Request\CancelPasswordResetRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResetPassword\Cancel\Response\CancelPasswordResetResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ResetTokenNotFoundException
     */
    public function execute(
        CancelPasswordResetRequest $request,
    ): CancelPasswordResetResponse {
        $this->requestValidator->validate($request, 'cancel_password_reset_request');

        try {
            $validToken = $this->passwordReset->validateToken($request->token);
        } catch (InvalidResetTokenException|ExpiredResetTokenException) {
            throw ResetTokenNotFoundException::byToken($request->token);
        }

        $this->passwordReset->removeToken($request->token);

        $this->logger->info('Password reset request cancelled', [
            'user_id' => $validToken->user->getId(),
        ]);

        return CancelPasswordResetResponse::success(
            message: $this->translator->trans('Password reset request has been cancelled.'),
        );
    }
}
