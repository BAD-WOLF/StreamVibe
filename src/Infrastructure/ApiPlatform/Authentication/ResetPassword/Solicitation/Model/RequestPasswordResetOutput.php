<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Solicitation\Model;

use App\Application\Authentication\DTO\ResetPassword\Solicitation\Response\RequestPasswordResetResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/reset-password:
 *
 *     {
 *       "success": true,
 *       "data": {"email_sent": true, "expires_at": "2026-10-28T15:04:05+00:00"},
 *       "message": "A password reset link has been sent to your email address."
 *     }
 *
 * Mapeado do RequestPasswordResetResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de RequestPasswordResetResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/ResetPassword/Solicitation/Model/SolicitationOutput,
 * com `data` como array genérico. Até aqui só alimentava o Swagger — o
 * JSON real era o toArray() do DTO, sem envelope (email_sent, expires_at,
 * request_id e user_id no nível de cima). request_id e user_id saíram:
 * davam o id interno da conta para quem só sabe o email.
 */
#[AutoMapFromDto(RequestPasswordResetResponse::class)]
final readonly class RequestPasswordResetOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Solicitation\Response\RequestPasswordResetResponse $data
     * @param string|null                                                                                     $message
     */
    public function __construct(
        public RequestPasswordResetResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
