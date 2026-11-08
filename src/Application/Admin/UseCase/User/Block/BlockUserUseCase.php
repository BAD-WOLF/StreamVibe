<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Block;

use App\Application\Admin\DTO\User\Block\Request\BlockUserRequest;
use App\Application\Admin\DTO\User\Block\Response\BlockUserResponse;
use App\Application\Admin\Port\UserSessionsInterface;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Bloqueia uma conta (POST /admin/users/{userId}/block, ROLE_ADMIN): o
 * login é recusado (UserCheckerAuthLogin), cada requisição com um access
 * token que ela já tinha também (UserCheckerApi), e as sessões são
 * encerradas.
 *
 * 409 quando a conta já está bloqueada, ou quando é o último owner ativo.
 *
 * As sessões são encerradas antes de gravar o bloqueio: se a gravação
 * falhar, a pessoa só precisa entrar de novo — o contrário deixaria uma
 * conta bloqueada com sessões abertas.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class BlockUserUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration $accounts
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Domain\User\Service\UserAdministrationPolicy   $policy
     * @param \App\Application\Admin\Port\UserSessionsInterface   $sessions
     * @param \App\Application\Admin\Shared\AuditTrail            $auditTrail
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private UserRepositoryInterface $userRepository,
        private UserAdministrationPolicy $policy,
        private UserSessionsInterface $sessions,
        private AuditTrail $auditTrail,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\Block\Request\BlockUserRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Block\Response\BlockUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(BlockUserRequest $request): BlockUserResponse {
        $this->requestValidator->validate($request, 'admin_block_user_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        if ($user->isBlocked()) {
            throw BusinessLogicException::noChange($this->translator->trans('This account is already blocked'));
        }

        if ($user->getRole() === Role::Owner) {
            $this->policy->assertKeepsAnOwner($user, $this->userRepository->countActiveWithRole(Role::Owner));
        }

        $revokedSessions = $this->sessions->revokeAllFor($user);
        $reason = trim($request->reason);
        $blockedAt = new DateTimeImmutable();

        $user->block($reason, $blockedAt);
        $this->auditTrail->record(
            AuditAction::UserBlocked,
            $actor,
            $user,
            ['reason' => $reason, 'revoked_sessions' => $revokedSessions],
            flush: false,
        );
        $this->userRepository->save($user);

        return BlockUserResponse::success(
            userId: (int) $user->getId(),
            blockedAt: $blockedAt,
            revokedSessions: $revokedSessions,
            message: $this->translator->trans('User blocked successfully'),
        );
    }
}
