<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\List\Response;

use App\Application\Admin\DTO\Shared\Response\PaginatedAdminListResponse;

/**
 * Resposta do ListUsersUseCase (GET /admin/users): uma página das contas,
 * da mais nova para a mais antiga.
 *
 *     {"users": [...], "current_page": 1, "total_pages": 3, "total_results": 47,
 *      "has_next": true, "has_previous": false, "next_page": 2, "previous_page": null}
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListUsersResponse extends PaginatedAdminListResponse {
    /**
     * @param list<\App\Application\Admin\DTO\Shared\Response\Model\AdminUserSummary> $users
     * @param int         $page
     * @param int         $totalPages
     * @param int         $totalResults
     * @param string|null $message
     */
    public function __construct(
        public private(set) array $users {
            /**
             * @return list<\App\Application\Admin\DTO\Shared\Response\Model\AdminUserSummary>
             */ get => $this->users;
        },
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ) {
        parent::__construct($page, $totalPages, $totalResults, $message);
    }

    /**
     * @param list<\App\Application\Admin\DTO\Shared\Response\Model\AdminUserSummary> $users
     * @param int         $page
     * @param int         $perPage
     * @param int         $totalResults
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        array $users,
        int $page,
        int $perPage,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            users: $users,
            page: $page,
            totalPages: self::pagesFor($totalResults, $perPage),
            totalResults: $totalResults,
            message: $message,
        );
    }
}
