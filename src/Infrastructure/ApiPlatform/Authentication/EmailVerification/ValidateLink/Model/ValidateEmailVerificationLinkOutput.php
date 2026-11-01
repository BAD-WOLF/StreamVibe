<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\ValidateLink\Model;

use App\Application\Authentication\DTO\EmailVerification\ValidateLink\Response\ValidateEmailVerificationLinkResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/verify/email/validate:
 *
 *     {
 *       "success": true,
 *       "data": {"user_id": 42, "valid": true, "already_verified": false},
 *       "message": "Verification token is valid"
 *     }
 *
 * Mapeado do ValidateEmailVerificationLinkResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ValidateEmailVerificationLinkResponse::$message. É
 * esta classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado quando a conferência saiu do
 * EmailVerificationController, que montava esse JSON na mão (com a
 * `message` repetida dentro de `data` e sem o campo `message` no nível de
 * cima).
 */
#[AutoMapFromDto(ValidateEmailVerificationLinkResponse::class)]
final readonly class ValidateEmailVerificationLinkOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\EmailVerification\ValidateLink\Response\ValidateEmailVerificationLinkResponse $data
     * @param string|null                                                                                                  $message
     */
    public function __construct(
        public ValidateEmailVerificationLinkResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
