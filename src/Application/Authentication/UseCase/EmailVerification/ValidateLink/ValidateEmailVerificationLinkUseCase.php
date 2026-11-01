<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\EmailVerification\ValidateLink;

use App\Application\Authentication\DTO\EmailVerification\ValidateLink\Request\ValidateEmailVerificationLinkRequest;
use App\Application\Authentication\DTO\EmailVerification\ValidateLink\Response\ValidateEmailVerificationLinkResponse;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\InvalidVerificationLinkException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Confere um link de verificação SEM verificar o email (para o cliente
 * mostrar "link válido / expirado" antes de confirmar).
 *
 * Link inválido não é erro aqui: responde 200 com valid=false.
 *
 * NOTA (out/2026): saiu do EmailVerificationController
 * (validateVerificationToken()). Os erros de parâmetro (400) e usuário
 * inexistente (404) saem pelo ExceptionListener — antes os dois caíam no
 * catch (Exception) do controller e viravam 500. E a resposta deixou de
 * ser sempre valid=false: o controller antigo conferia a URL da própria
 * consulta (/verify/email/validate), não a do link assinado; o
 * ValidateEmailVerificationLinkController agora monta a URL do link.
 */
final readonly class ValidateEmailVerificationLinkUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface             $userRepository
     * @param \App\Application\Authentication\Port\EmailVerificationInterface $emailVerification
     * @param \App\Application\Shared\RequestValidator                        $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface              $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailVerificationInterface $emailVerification,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\EmailVerification\ValidateLink\Request\ValidateEmailVerificationLinkRequest $request
     *
     * @return \App\Application\Authentication\DTO\EmailVerification\ValidateLink\Response\ValidateEmailVerificationLinkResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function execute(
        ValidateEmailVerificationLinkRequest $request,
    ): ValidateEmailVerificationLinkResponse {
        $this->requestValidator->validate(
            $request,
            'validate_email_verification_link_request',
            ['user_id' => $request->userId],
        );

        $user = $this->userRepository->findById($request->userId)
            ?? throw UserNotFoundException::withId($request->userId);

        if ($user->isVerified()) {
            return ValidateEmailVerificationLinkResponse::success(
                userId: $request->userId,
                valid: true,
                alreadyVerified: true,
                message: $this->translator->trans('Email is already verified'),
            );
        }

        try {
            $this->emailVerification->validateLink($request->signedUrl, $user);
        } catch (InvalidVerificationLinkException) {
            return ValidateEmailVerificationLinkResponse::success(
                userId: $request->userId,
                valid: false,
                alreadyVerified: false,
                message: $this->translator->trans('Verification token is invalid or expired'),
            );
        }

        return ValidateEmailVerificationLinkResponse::success(
            userId: $request->userId,
            valid: true,
            alreadyVerified: false,
            message: $this->translator->trans('Verification token is valid'),
        );
    }
}
