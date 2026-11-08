<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Delete;

use App\Application\Admin\DTO\User\Delete\Request\DeleteUserRequest;
use App\Application\Admin\DTO\User\Delete\Response\DeleteUserResponse;
use App\Application\Admin\Port\UserSessionsInterface;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exclui uma conta (DELETE /admin/users/{userId}, ROLE_ADMIN): a conta, os
 * pedidos de troca de senha dela (ON DELETE CASCADE) e as sessões.
 *
 * As sessões saem primeiro, de propósito: o refresh token guarda o email,
 * e não o id — sem apagar, uma conta nova cadastrada depois com o mesmo
 * email herdaria as sessões da excluída.
 *
 * 409 quando é o último owner ativo. A entrada do log continua lá depois
 * (sem chave estrangeira para a conta).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class DeleteUserUseCase {
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
     * @param \App\Application\Admin\DTO\User\Delete\Request\DeleteUserRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Delete\Response\DeleteUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(DeleteUserRequest $request): DeleteUserResponse {
        $this->requestValidator->validate($request, 'admin_delete_user_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        if ($user->getRole() === Role::Owner) {
            $this->policy->assertKeepsAnOwner($user, $this->userRepository->countActiveWithRole(Role::Owner));
        }

        // O Doctrine zera o id da entidade depois de excluir.
        $userId = (int) $user->getId();

        $revokedSessions = $this->sessions->revokeAllFor($user);
        $this->auditTrail->record(
            AuditAction::UserDeleted,
            $actor,
            $user,
            ['revoked_sessions' => $revokedSessions],
            flush: false,
        );
        $this->userRepository->remove($user);

        return DeleteUserResponse::success(
            userId: $userId,
            revokedSessions: $revokedSessions,
            message: $this->translator->trans('User deleted successfully'),
        );
    }
}
