<?php
declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final readonly class CancelResetInput {
    /**
     * @param string $token
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Token is required')]
        #[Assert\Length(
            min: 1,
            minMessage: 'Token cannot be empty'
        )]
        public string $token,
    ) {
    }
}
