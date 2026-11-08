<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Unblock;

use App\Application\Admin\DTO\User\Unblock\Request\UnblockUserRequest;
use App\Application\Admin\DTO\User\Unblock\Response\UnblockUserResponse;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Libera uma conta bloqueada (POST /admin/users/{userId}/unblock,
 * ROLE_ADMIN): ela volta a poder entrar. O motivo do bloqueio sai da conta
 * e fica no log (previous_reason).
 *
 * 409 quando a conta não está bloqueada.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class UnblockUserUseCase {
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
     * @param \App\Application\Admin\DTO\User\Unblock\Request\UnblockUserRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Unblock\Response\UnblockUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(UnblockUserRequest $request): UnblockUserResponse {
        $this->requestValidator->validate($request, 'admin_unblock_user_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        if (!$user->isBlocked()) {
            throw BusinessLogicException::noChange($this->translator->trans('This account is not blocked'));
        }

        $previousReason = $user->getBlockedReason();

        $user->unblock();
        $this->auditTrail->record(
            AuditAction::UserUnblocked,
            $actor,
            $user,
            ['previous_reason' => $previousReason],
            flush: false,
        );
        $this->userRepository->save($user);

        return UnblockUserResponse::success(
            userId: (int) $user->getId(),
            blockedAt: $user->getBlockedAt(),
            message: $this->translator->trans('User unblocked successfully'),
        );
    }
}
