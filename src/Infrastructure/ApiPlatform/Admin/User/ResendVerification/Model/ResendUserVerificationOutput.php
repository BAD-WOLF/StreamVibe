<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Admin\User\ResendVerification\Model;

use App\Application\Admin\DTO\User\ResendVerification\Response\ResendUserVerificationResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/admin/users/{userId}/resend-verification:
 *
 *     {"success": true, "data": {"user_id": 42, "email_sent": true}, "message": "Verification email sent successfully"}
 *
 * Mapeado do ResendUserVerificationResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ResendUserVerificationResponse::$message. É esta classe que o
 * controller serializa e que o Swagger documenta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AutoMapFromDto(ResendUserVerificationResponse::class)]
final readonly class ResendUserVerificationOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Admin\DTO\User\ResendVerification\Response\ResendUserVerificationResponse $data
     * @param string|null $message
     */
    public function __construct(
        public ResendUserVerificationResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
