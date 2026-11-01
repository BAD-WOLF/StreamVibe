<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Cancel\Model;

use App\Application\Authentication\DTO\ResetPassword\Cancel\Response\CancelPasswordResetResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/reset-password/cancel:
 *
 *     {
 *       "success": true,
 *       "data": {"cancelled": true},
 *       "message": "Password reset request has been cancelled."
 *     }
 *
 * Mapeado do CancelPasswordResetResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de CancelPasswordResetResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/ResetPassword/Cancel/Model/CancelResetOutput,
 * mapeado do mesmo DTO do pedido de troca (ResetPasswordResponse) — o
 * JSON real trazia request_id, expires_at, email_sent e user_id vazios.
 */
#[AutoMapFromDto(CancelPasswordResetResponse::class)]
final readonly class CancelPasswordResetOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Cancel\Response\CancelPasswordResetResponse $data
     * @param string|null                                                                               $message
     */
    public function __construct(
        public CancelPasswordResetResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
