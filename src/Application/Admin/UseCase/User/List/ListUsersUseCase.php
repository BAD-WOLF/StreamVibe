<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\List;

use App\Application\Admin\DTO\Shared\Response\Model\AdminUserSummary;
use App\Application\Admin\DTO\User\List\Request\ListUsersRequest;
use App\Application\Admin\DTO\User\List\Response\ListUsersResponse;
use App\Application\Shared\RequestValidator;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Role;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Lista as contas, 20 por página, da mais nova para a mais antiga
 * (GET /admin/users, ROLE_ADMIN).
 *
 * Todo ROLE_ADMIN vê todas as contas, inclusive as que não pode alterar
 * (a regra de quem administra quem vale para as ações).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class ListUsersUseCase {
    private const int PER_PAGE = 20;

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
     * @param \App\Application\Admin\DTO\User\List\Request\ListUsersRequest $request
     *
     * @return \App\Application\Admin\DTO\User\List\Response\ListUsersResponse
     * @throws \App\Domain\Exception\UserValidationException
     */
    public function execute(ListUsersRequest $request): ListUsersResponse {
        $this->requestValidator->validate($request, 'admin_list_users_request', ['page' => $request->page]);

        $search = trim((string) $request->search);
        $found = $this->userRepository->findPage(
            $request->page,
            self::PER_PAGE,
            $search === '' ? null : $search,
            $request->role === null ? null : Role::from($request->role),
            $request->verified,
            $request->blocked,
        );

        return ListUsersResponse::success(
            users: array_map(
                static fn(User $user): AdminUserSummary => new AdminUserSummary(
                    id: (int) $user->getId(),
                    email: (string) $user->getEmail(),
                    role: $user->getRole()->value,
                    verified: $user->isVerified(),
                    createdAt: $user->getCreatedAt(),
                    lastLoginAt: $user->getLastLoginAt(),
                    blockedAt: $user->getBlockedAt(),
                ),
                $found['users'],
            ),
            page: $request->page,
            perPage: self::PER_PAGE,
            totalResults: $found['total'],
            message: $this->translator->trans('Users retrieved successfully'),
        );
    }
}
