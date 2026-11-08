<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Block\Model;

use App\Application\Admin\DTO\User\Block\Response\BlockUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/admin/users/{userId}/block:
 *
 *     {"success": true, "data": {"user_id": 42, "blocked_at": "2026-11-08T12:00:00+00:00", "revoked_sessions": 2},
 *      "message": "User blocked successfully"}
 *
 * Mapeado do BlockUserResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de BlockUserResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(BlockUserResponse::class)]
final readonly class BlockUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Block\Response\BlockUserResponse $data
     * @param string|null $message
     */
    public function __construct(
        public BlockUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
