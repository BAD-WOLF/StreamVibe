<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\CheckStatus\Response;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do CheckPasswordResetStatusUseCase: o `data` de
 * GET /reset-password/status/{token}.
 *
 *     {"valid": true, "expired": false, "used": false, "expires_at": "2026-10-28T15:04:05+00:00"}
 *
 * Só existe para token válido: token inválido é 400 e vencido é 410
 * (ExceptionListener). Por isso valid/expired/used são sempre
 * true/false/false — ficam no contrato porque o cliente já os lia
 * (um token usado é apagado e passa a ser "inválido").
 *
 * NOTA (out/2026): era o DTO/Reset/Response/ResetPasswordStatusResponse.
 * expires_at sai em ISO 8601 (RFC 3339).
 */
final class CheckPasswordResetStatusResponse {
    // Declaradas antes do construtor para saírem antes de expires_at no JSON.
    public bool $valid {
        /**
         * @return bool
         */
        get => true;
    }

    public bool $expired {
        /**
         * @return bool
         */
        get => false;
    }

    public bool $used {
        /**
         * @return bool
         */
        get => false;
    }

    /**
     * @param \DateTimeImmutable $expiresAt
     * @param string|null        $message
     */
    public function __construct(
        #[SerializedName('expires_at')]
        public private(set) DateTimeImmutable $expiresAt {
            /**
             * @return \DateTimeImmutable
             */ get => $this->expiresAt;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param \DateTimeImmutable $expiresAt
     * @param string|null        $message
     *
     * @return self
     */
    public static function valid(DateTimeImmutable $expiresAt, ?string $message = null): self {
        return new self(expiresAt: $expiresAt, message: $message);
    }
}
