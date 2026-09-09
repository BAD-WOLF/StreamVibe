<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResendVerification\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class ResendVerificationInput {
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email = '';
}
