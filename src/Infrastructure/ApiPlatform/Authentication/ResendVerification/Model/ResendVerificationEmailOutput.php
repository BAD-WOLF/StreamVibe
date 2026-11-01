<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResendVerification\Model;

use App\Application\Authentication\DTO\ResendVerification\Response\ResendVerificationEmailResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/resend-verification:
 *
 *     {
 *       "success": true,
 *       "data": {"email_sent": true},
 *       "message": "Verification email has been sent. Please check your inbox."
 *     }
 *
 * Mapeado do ResendVerificationEmailResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ResendVerificationEmailResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/ResendVerification/Model/ResendVerificationResponse,
 * mapeado de um DTO que ninguém usava (o reenvio devolvia o
 * RegisterUserResponse). Saíram emailSent/error no nível de cima,
 * fromArray(), toArray() e os getters.
 */
#[AutoMapFromDto(ResendVerificationEmailResponse::class)]
final readonly class ResendVerificationEmailOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResendVerification\Response\ResendVerificationEmailResponse $data
     * @param string|null                                                                                 $message
     */
    public function __construct(
        public ResendVerificationEmailResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
