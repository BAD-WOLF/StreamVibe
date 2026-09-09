<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\Register\Model;

/**
 *
 */
final readonly class RegisterUserInput {
    /**
     * @param string $email
     * @param string $password
     * @param bool   $agreeTerms
     */
    public function __construct(
        public string $email,
        public string $password,
        public bool $agreeTerms = false,
    ) {
    }
}

