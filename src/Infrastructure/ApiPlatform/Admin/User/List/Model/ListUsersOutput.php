<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\List\Model;

use App\Application\Admin\DTO\User\List\Response\ListUsersResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/admin/users:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "users": [{"id": 42, "email": "ana@example.com", "role": "ROLE_USER", "verified": true,
 *                    "created_at": "2026-11-08T12:00:00+00:00", "last_login_at": null, "blocked_at": null}],
 *         "current_page": 1, "total_pages": 1, "total_results": 1,
 *         "has_next": false, "has_previous": false, "next_page": null, "previous_page": null
 *       },
 *       "message": "Users retrieved successfully"
 *     }
 *
 * Mapeado do ListUsersResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ListUsersResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(ListUsersResponse::class)]
final readonly class ListUsersOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\List\Response\ListUsersResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ListUsersResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
