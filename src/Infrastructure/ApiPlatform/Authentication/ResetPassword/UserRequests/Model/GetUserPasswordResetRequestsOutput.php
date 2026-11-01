<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\UserRequests\Model;

use App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\GetUserPasswordResetRequestsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/reset-password/requests/{userId}:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "user_id": 42,
 *         "active_requests": [
 *           {"id": 7, "created_at": "2026-10-28T14:04:05+00:00", "expires_at": "2026-10-28T15:04:05+00:00"}
 *         ]
 *       },
 *       "message": "User reset requests retrieved successfully"
 *     }
 *
 * Mapeado do GetUserPasswordResetRequestsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetUserPasswordResetRequestsResponse::$message. É
 * esta classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/ResetPassword/UserRequests/Model/UserResetRequestsOutput,
 * que ninguém usava (o controller montava o JSON na mão, sem DTO). O
 * `count` saiu: era o tamanho de active_requests.
 */
#[AutoMapFromDto(GetUserPasswordResetRequestsResponse::class)]
final readonly class GetUserPasswordResetRequestsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\GetUserPasswordResetRequestsResponse $data
     * @param string|null                                                                                             $message
     */
    public function __construct(
        public GetUserPasswordResetRequestsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
