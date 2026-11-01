<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Status\Model;

use App\Application\Authentication\DTO\EmailVerification\Status\Response\GetEmailVerificationStatusResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/verify/email/status/{userId}:
 *
 *     {
 *       "success": true,
 *       "data": {"user_id": 42, "verified": false},
 *       "message": "User email verification required"
 *     }
 *
 * Mapeado do GetEmailVerificationStatusResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetEmailVerificationStatusResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado quando a consulta saiu do
 * EmailVerificationController. O `data` antigo trazia email,
 * is_verified e verification_required; agora só user_id e verified.
 */
#[AutoMapFromDto(GetEmailVerificationStatusResponse::class)]
final readonly class GetEmailVerificationStatusOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\EmailVerification\Status\Response\GetEmailVerificationStatusResponse $data
     * @param string|null                                                                                          $message
     */
    public function __construct(
        public GetEmailVerificationStatusResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
