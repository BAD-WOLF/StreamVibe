<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\AuditLog\List\Response;

use App\Application\Admin\DTO\Shared\Response\PaginatedAdminListResponse;

/**
 * Resposta do ListAuditLogUseCase (GET /admin/audit-log): uma página do
 * log, da entrada mais nova para a mais antiga.
 *
 *     {"entries": [...], "current_page": 1, "total_pages": 1, "total_results": 3,
 *      "has_next": false, "has_previous": false, "next_page": null, "previous_page": null}
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class ListAuditLogResponse extends PaginatedAdminListResponse {
    /**
     * @param list<\App\Application\Admin\DTO\Shared\Response\Model\AuditLogEntrySummary> $entries
     * @param int         $page
     * @param int         $totalPages
     * @param int         $totalResults
     * @param string|null $message
     */
    public function __construct(
        public private(set) array $entries {
            /**
             * @return list<\App\Application\Admin\DTO\Shared\Response\Model\AuditLogEntrySummary>
             */ get => $this->entries;
        },
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ) {
        parent::__construct($page, $totalPages, $totalResults, $message);
    }

    /**
     * @param list<\App\Application\Admin\DTO\Shared\Response\Model\AuditLogEntrySummary> $entries
     * @param int         $page
     * @param int         $perPage
     * @param int         $totalResults
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        array $entries,
        int $page,
        int $perPage,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            entries: $entries,
            page: $page,
            totalPages: self::pagesFor($totalResults, $perPage),
            totalResults: $totalResults,
            message: $message,
        );
    }
}
