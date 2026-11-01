<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Confirm\Model;

use App\Application\Authentication\DTO\ResetPassword\Confirm\Response\ConfirmPasswordResetResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/reset-password/confirm:
 *
 *     {
 *       "success": true,
 *       "data": {"password_reset": true},
 *       "message": "Your password has been reset successfully."
 *     }
 *
 * Mapeado do ConfirmPasswordResetResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de ConfirmPasswordResetResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado com o endpoint de troca de senha.
 */
#[AutoMapFromDto(ConfirmPasswordResetResponse::class)]
final readonly class ConfirmPasswordResetOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Confirm\Response\ConfirmPasswordResetResponse $data
     * @param string|null                                                                                 $message
     */
    public function __construct(
        public ConfirmPasswordResetResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
