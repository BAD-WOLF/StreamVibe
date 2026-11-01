<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\EmailVerification\Status;

use App\Application\Authentication\DTO\EmailVerification\Status\Request\GetEmailVerificationStatusRequest;
use App\Application\Authentication\DTO\EmailVerification\Status\Response\GetEmailVerificationStatusResponse;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Diz se o email de uma conta já foi verificado. Usado pelo cliente
 * depois do cadastro, enquanto o usuário não clica no link (ele ainda não
 * consegue logar, por isso a rota é pública).
 *
 * NOTA (out/2026): saiu do EmailVerificationController. A resposta não
 * traz mais o email da conta (ver GetEmailVerificationStatusResponse), e
 * usuário inexistente agora é 404 pelo ExceptionListener — antes o
 * UserNotFoundException era capturado pelo catch (Exception) do próprio
 * controller e virava 500.
 */
final readonly class GetEmailVerificationStatusUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\EmailVerification\Status\Request\GetEmailVerificationStatusRequest $request
     *
     * @return \App\Application\Authentication\DTO\EmailVerification\Status\Response\GetEmailVerificationStatusResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function execute(
        GetEmailVerificationStatusRequest $request,
    ): GetEmailVerificationStatusResponse {
        $this->requestValidator->validate(
            $request,
            'email_verification_status_request',
            ['user_id' => $request->userId],
        );

        $user = $this->userRepository->findById($request->userId)
            ?? throw UserNotFoundException::withId($request->userId);

        return GetEmailVerificationStatusResponse::success(
            userId: $request->userId,
            verified: $user->isVerified(),
            message: $this->translator->trans(
                $user->isVerified()
                    ? 'User email is verified'
                    : 'User email verification required',
            ),
        );
    }
}
