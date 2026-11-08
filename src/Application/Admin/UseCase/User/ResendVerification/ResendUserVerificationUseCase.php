<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\ResendVerification;

use App\Application\Admin\DTO\User\ResendVerification\Request\ResendUserVerificationRequest;
use App\Application\Admin\DTO\User\ResendVerification\Response\ResendUserVerificationResponse;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\BusinessLogicException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Manda de novo o email de confirmação de uma conta
 * (POST /admin/users/{userId}/resend-verification, ROLE_ADMIN) — o mesmo
 * email do POST /resend-verification.
 *
 * 409 quando a conta já está confirmada; 503 quando o email não pôde ser
 * enviado (nesse caso nada entra no log).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class ResendUserVerificationUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration              $accounts
     * @param \App\Application\Authentication\Port\EmailVerificationInterface $emailVerification
     * @param \App\Application\Admin\Shared\AuditTrail                         $auditTrail
     * @param \App\Application\Shared\RequestValidator                         $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface               $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private EmailVerificationInterface $emailVerification,
        private AuditTrail $auditTrail,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\ResendVerification\Request\ResendUserVerificationRequest $request
     *
     * @return \App\Application\Admin\DTO\User\ResendVerification\Response\ResendUserVerificationResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\EmailDeliveryException
     */
    public function execute(ResendUserVerificationRequest $request): ResendUserVerificationResponse {
        $this->requestValidator->validate($request, 'admin_resend_verification_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        if ($user->isVerified()) {
            throw BusinessLogicException::noChange($this->translator->trans('This account is already verified'));
        }

        $this->emailVerification->sendConfirmationEmail($user, isResend: true);
        $this->auditTrail->record(AuditAction::VerificationResent, $actor, $user, [], flush: true);

        return ResendUserVerificationResponse::success(
            userId: (int) $user->getId(),
            emailSent: true,
            message: $this->translator->trans('Verification email sent successfully'),
        );
    }
}
