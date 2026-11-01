<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify\Model;

use App\Application\Authentication\DTO\EmailVerification\Verify\Response\VerifyEmailResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/verify/email:
 *
 *     {
 *       "success": true,
 *       "data": {"user_id": 42, "already_verified": false, "verified": true},
 *       "message": "Email verified successfully"
 *     }
 *
 * Mapeado do VerifyEmailResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * VerifyEmailResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado quando a verificação saiu do
 * EmailVerificationController, que montava esse JSON na mão (mesmos
 * campos em `data`).
 */
#[AutoMapFromDto(VerifyEmailResponse::class)]
final readonly class VerifyEmailOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\EmailVerification\Verify\Response\VerifyEmailResponse $data
     * @param string|null                                                                           $message
     */
    public function __construct(
        public VerifyEmailResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
