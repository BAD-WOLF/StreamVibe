<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Role;

use App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest;
use App\Application\Admin\DTO\User\Role\Response\ChangeUserRoleResponse;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Muda o papel de uma conta (POST /admin/users/{userId}/role, ROLE_ADMIN,
 * e o comando app:user:role).
 *
 * - Pela API: o admin só mexe em contas abaixo do papel dele e só dá
 *   papéis abaixo do dele (até ROLE_DEV); o owner, em qualquer outra conta
 *   e qualquer papel (UserAdministrationPolicy).
 * - Pelo console (actorId null): sem essas regras — é como se dá o
 *   primeiro ROLE_OWNER.
 * - Nos dois: 409 quando a conta já tem o papel, ou quando ela é o último
 *   owner ativo e deixaria de ser.
 *
 * O papel novo vale na próxima requisição da pessoa: a cada requisição a
 * conta é lida do banco, e o papel junto.
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
final readonly class ChangeUserRoleUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration $accounts
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Domain\User\Service\UserAdministrationPolicy   $policy
     * @param \App\Application\Admin\Shared\AuditTrail            $auditTrail
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private UserRepositoryInterface $userRepository,
        private UserAdministrationPolicy $policy,
        private AuditTrail $auditTrail,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Role\Response\ChangeUserRoleResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(ChangeUserRoleRequest $request): ChangeUserRoleResponse {
        $this->requestValidator->validate($request, 'admin_change_user_role_request', ['user_id' => $request->userId]);

        $role = Role::from($request->role);

        if ($request->actorId === null) {
            $actor = null;
            $user = $this->accounts->account($request->userId);
        } else {
            $actor = $this->accounts->actor($request->actorId);
            $user = $this->accounts->managedAccount($actor, $request->userId);
            $this->policy->assertCanAssign($actor, $role);
        }

        $previousRole = $user->getRole();

        if ($previousRole === $role) {
            throw BusinessLogicException::noChange($this->translator->trans('This account already has this role'));
        }

        if ($previousRole === Role::Owner) {
            $this->policy->assertKeepsAnOwner($user, $this->userRepository->countActiveWithRole(Role::Owner));
        }

        $user->assignRole($role);
        $this->auditTrail->record(
            AuditAction::RoleChanged,
            $actor,
            $user,
            ['previous_role' => $previousRole->value],
            flush: false,
        );
        $this->userRepository->save($user);

        return ChangeUserRoleResponse::success(
            userId: (int) $user->getId(),
            role: $role->value,
            previousRole: $previousRole->value,
            message: $this->translator->trans('User role changed successfully'),
        );
    }
}
