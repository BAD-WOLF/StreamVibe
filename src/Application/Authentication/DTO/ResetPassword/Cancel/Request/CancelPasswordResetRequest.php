<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\Cancel\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do CancelPasswordResetUseCase (POST /reset-password/cancel).
 *
 * NOTA (out/2026): era o DTO/Reset/Request/CancelResetRequest. Saíram
 * userAgent/ipAddress (o controller os lia e o cancel() nunca recebia),
 * o Length(min: 1) (repetia o NotBlank), asArray/toArray() e os getters
 * de compatibilidade.
 */
final class CancelPasswordResetRequest {
    /**
     * @param string $token
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Token cannot be blank')]
        public private(set) string $token {
            /**
             * @return string
             */ get => $this->token;
        },
    ) {
    }
}
