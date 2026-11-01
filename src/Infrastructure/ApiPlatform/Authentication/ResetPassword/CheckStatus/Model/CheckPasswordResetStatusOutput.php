<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\CheckStatus\Model;

use App\Application\Authentication\DTO\ResetPassword\CheckStatus\Response\CheckPasswordResetStatusResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/reset-password/status/{token}:
 *
 *     {
 *       "success": true,
 *       "data": {"valid": true, "expired": false, "used": false, "expires_at": "2026-10-28T15:04:05+00:00"},
 *       "message": "Reset token is valid"
 *     }
 *
 * Mapeado do CheckPasswordResetStatusResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de CheckPasswordResetStatusResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/ResetPassword/CheckStatus/Model/CheckResetStatusOutput.
 * Até aqui só alimentava o Swagger — o JSON real era o toArray() do DTO,
 * sem envelope.
 */
#[AutoMapFromDto(CheckPasswordResetStatusResponse::class)]
final readonly class CheckPasswordResetStatusOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\CheckStatus\Response\CheckPasswordResetStatusResponse $data
     * @param string|null                                                                                         $message
     */
    public function __construct(
        public CheckPasswordResetStatusResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
