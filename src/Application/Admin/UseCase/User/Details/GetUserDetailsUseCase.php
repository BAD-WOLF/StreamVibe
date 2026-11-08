<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Details;

use App\Application\Admin\DTO\User\Details\Request\GetUserDetailsRequest;
use App\Application\Admin\DTO\User\Details\Response\GetUserDetailsResponse;
use App\Application\Admin\Port\UserSessionsInterface;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary;
use App\Application\Shared\RequestValidator;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tudo o que a aplicação sabe de uma conta (GET /admin/users/{userId},
 * ROLE_ADMIN): os dados dela, as sessões ativas, os pedidos de troca de
 * senha ainda válidos e o que quem pediu pode fazer com ela.
 *
 * Todo ROLE_ADMIN vê qualquer conta; can_manage e assignable_roles dizem
 * o que ele pode alterar (UserAdministrationPolicy).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class GetUserDetailsUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration                                $accounts
     * @param \App\Domain\User\Service\UserAdministrationPolicy                                  $policy
     * @param \App\Application\Admin\Port\UserSessionsInterface                                  $sessions
     * @param \App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface $resetRequestRepository
     * @param \App\Application\Shared\RequestValidator                                           $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface                                 $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private UserAdministrationPolicy $policy,
        private UserSessionsInterface $sessions,
        private ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\Details\Request\GetUserDetailsRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Details\Response\GetUserDetailsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function execute(GetUserDetailsRequest $request): GetUserDetailsResponse {
        $this->requestValidator->validate($request, 'admin_user_details_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->account($request->userId);

        $resetRequests = array_map(
            static fn(ResetPasswordSolicitation $resetRequest): PasswordResetRequestSummary => new PasswordResetRequestSummary(
                id: (int) $resetRequest->getId(),
                createdAt: DateTimeImmutable::createFromInterface($resetRequest->getRequestedAt()),
                expiresAt: DateTimeImmutable::createFromInterface($resetRequest->getExpiresAt()),
            ),
            $this->resetRequestRepository->findActiveRequestsForUser($user),
        );

        return GetUserDetailsResponse::success(
            id: (int) $user->getId(),
            email: (string) $user->getEmail(),
            role: $user->getRole()->value,
            verified: $user->isVerified(),
            createdAt: $user->getCreatedAt(),
            lastLoginAt: $user->getLastLoginAt(),
            blockedAt: $user->getBlockedAt(),
            blockedReason: $user->getBlockedReason(),
            activeSessions: $this->sessions->countActiveFor($user),
            passwordResetRequests: array_values($resetRequests),
            canManage: $this->policy->canManage($actor, $user),
            assignableRoles: array_map(
                static fn(Role $role): string => $role->value,
                $this->policy->assignableRoles($actor, $user),
            ),
            message: $this->translator->trans('User retrieved successfully'),
        );
    }
}
