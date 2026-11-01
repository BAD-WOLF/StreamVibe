<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\EmailVerification\Verify;

use App\Application\Authentication\DTO\EmailVerification\Verify\Request\VerifyEmailRequest;
use App\Application\Authentication\DTO\EmailVerification\Verify\Response\VerifyEmailResponse;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Verifica o email a partir do link assinado que o usuário recebeu.
 *
 * - link válido: marca a conta como verificada (200, already_verified
 *   false);
 * - conta já verificada: 200 com already_verified true (o link pode ser
 *   aberto de novo sem erro);
 * - link inválido, expirado ou de outro usuário: 400
 *   (InvalidVerificationLinkException);
 * - usuário inexistente: 404.
 *
 * NOTA (out/2026): a lógica estava no EmailVerificationController
 * (buscar o usuário, validar o link, salvar), com um catch (Exception)
 * que respondia 500 com a mensagem crua e um JSON de erro próprio para
 * link inválido. Agora é UseCase, e os erros saem pelo ExceptionListener
 * no formato padrão.
 */
final readonly class VerifyEmailUseCase {
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
     * @param \App\Application\Authentication\DTO\EmailVerification\Verify\Request\VerifyEmailRequest $request
     *
     * @return \App\Application\Authentication\DTO\EmailVerification\Verify\Response\VerifyEmailResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\InvalidVerificationLinkException
     */
    public function execute(VerifyEmailRequest $request): VerifyEmailResponse {
        $this->requestValidator->validate(
            $request,
            'verify_email_request',
            ['user_id' => $request->userId],
        );

        $user = $this->userRepository->findById($request->userId)
            ?? throw UserNotFoundException::withId($request->userId);

        if ($user->isVerified()) {
            return VerifyEmailResponse::success(
                userId: $request->userId,
                alreadyVerified: true,
                message: $this->translator->trans('Email is already verified'),
            );
        }

        $this->emailVerification->validateLink($request->signedUrl, $user);

        $user->setIsVerified(true);
        $this->userRepository->save($user, flush: true);

        $this->logger->info('Email verified', ['user_id' => $request->userId]);

        return VerifyEmailResponse::success(
            userId: $request->userId,
            alreadyVerified: false,
            message: $this->translator->trans('Email verified successfully'),
        );
    }
}
