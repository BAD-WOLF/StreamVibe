<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Verify;

use App\Application\Admin\DTO\User\Verify\Request\VerifyUserRequest;
use App\Application\Admin\DTO\User\Verify\Response\VerifyUserResponse;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Confirma o email de uma conta no lugar da pessoa
 * (POST /admin/users/{userId}/verify, ROLE_ADMIN): ela passa a poder
 * entrar sem abrir o link do email.
 *
 * 409 quando a conta já está confirmada (nada muda, nada entra no log).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class VerifyUserUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration $accounts
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Application\Admin\Shared\AuditTrail            $auditTrail
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private UserRepositoryInterface $userRepository,
        private AuditTrail $auditTrail,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\Verify\Request\VerifyUserRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Verify\Response\VerifyUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(VerifyUserRequest $request): VerifyUserResponse {
        $this->requestValidator->validate($request, 'admin_verify_user_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        if ($user->isVerified()) {
            throw BusinessLogicException::noChange($this->translator->trans('This account is already verified'));
        }

        $user->setIsVerified(true);
        $this->auditTrail->record(AuditAction::UserVerified, $actor, $user, [], flush: false);
        $this->userRepository->save($user);

        return VerifyUserResponse::success(
            userId: (int) $user->getId(),
            verified: true,
            message: $this->translator->trans('User verified successfully'),
        );
    }
}
