<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\Status\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetEmailVerificationStatusUseCase
 * (GET /verify/email/status/{userId}).
 *
 * NOTA (out/2026): criado quando a consulta saiu do
 * EmailVerificationController para um UseCase.
 */
final class GetEmailVerificationStatusRequest {
    /**
     * @param int $userId
     */
    public function __construct(
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
    ) {
    }
}
