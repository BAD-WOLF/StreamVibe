<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Unblock\Model;

use App\Application\Admin\DTO\User\Unblock\Response\UnblockUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/admin/users/{userId}/unblock:
 *
 *     {"success": true, "data": {"user_id": 42, "blocked_at": null}, "message": "User unblocked successfully"}
 *
 * Mapeado do UnblockUserResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de UnblockUserResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(UnblockUserResponse::class)]
final readonly class UnblockUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Unblock\Response\UnblockUserResponse $data
     * @param string|null $message
     */
    public function __construct(
        public UnblockUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
