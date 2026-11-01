<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResetPassword\CheckStatus;

use App\Application\Authentication\DTO\ResetPassword\CheckStatus\Request\CheckPasswordResetStatusRequest;
use App\Application\Authentication\DTO\ResetPassword\CheckStatus\Response\CheckPasswordResetStatusResponse;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Shared\RequestValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Confere se o token do link de troca de senha ainda vale
 * (GET /reset-password/status/{token}): 200 com a validade, 400 se o
 * token é inválido/inexistente, 410 se venceu.
 *
 * NOTA (out/2026): era ResetUserPasswordUseCase::checkStatus(). A
 * tradução das exceções do bundle (que lá ocupava seis blocos catch) foi
 * para o adaptador da PasswordResetInterface, e a validade agora é a da
 * solicitação do próprio token — o checkStatus() buscava com
 * findByUser(), a mais recente do usuário, que podia ser outra.
 */
final readonly class CheckPasswordResetStatusUseCase {
    /**
     * @param \App\Application\Authentication\Port\PasswordResetInterface $passwordReset
     * @param \App\Application\Shared\RequestValidator                    $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     */
    public function __construct(
        private PasswordResetInterface $passwordReset,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\CheckStatus\Request\CheckPasswordResetStatusRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResetPassword\CheckStatus\Response\CheckPasswordResetStatusResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\InvalidResetTokenException
     * @throws \App\Domain\Exception\ExpiredResetTokenException
     */
    public function execute(
        CheckPasswordResetStatusRequest $request,
    ): CheckPasswordResetStatusResponse {
        $this->requestValidator->validate($request, 'check_password_reset_status_request');

        $validToken = $this->passwordReset->validateToken($request->token);

        return CheckPasswordResetStatusResponse::valid(
            expiresAt: $validToken->expiresAt,
            message: $this->translator->trans('Reset token is valid'),
        );
    }
}
