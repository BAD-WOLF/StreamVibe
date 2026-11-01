<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\ValidateLink\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ValidateEmailVerificationLinkUseCase: o link de verificação
 * a conferir sem verificar o email
 * (GET /verify/email/validate?expires=&id=&signature=&token=).
 *
 * NOTA (out/2026): criado quando a conferência saiu do
 * EmailVerificationController para um UseCase. Mesmos campos do
 * VerifyEmailRequest.
 */
final class ValidateEmailVerificationLinkRequest {
    /**
     * @param int    $userId
     * @param string $signedUrl
     */
    public function __construct(
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },

        #[Assert\NotBlank(message: 'Verification link cannot be blank')]
        public private(set) string $signedUrl {
            /**
             * @return string
             */ get => $this->signedUrl;
        },
    ) {
    }
}
