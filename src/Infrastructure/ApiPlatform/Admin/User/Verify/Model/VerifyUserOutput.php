<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\Verify\Model;

use App\Application\Admin\DTO\User\Verify\Response\VerifyUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/admin/users/{userId}/verify:
 *
 *     {"success": true, "data": {"user_id": 42, "verified": true}, "message": "User verified successfully"}
 *
 * Mapeado do VerifyUserResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de VerifyUserResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(VerifyUserResponse::class)]
final readonly class VerifyUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\Verify\Response\VerifyUserResponse $data
     * @param string|null $message
     */
    public function __construct(
        public VerifyUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
