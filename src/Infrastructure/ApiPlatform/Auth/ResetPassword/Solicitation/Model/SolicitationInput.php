<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Model;

/**
 *
 */
readonly class SolicitationInput {
    /**
     * @param string $email
     */
    public function __construct(
        public string $email,
    ) {
    }
}
