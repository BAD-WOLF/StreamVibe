<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\Verify\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do VerifyEmailUseCase: o link de verificação que o usuário
 * abriu (GET /verify/email?expires=&id=&signature=&token=).
 *
 * - userId: o `id` da query, para achar o usuário;
 * - signedUrl: a URL completa, para conferir a assinatura.
 *
 * NOTA (out/2026): criado quando a verificação saiu do
 * EmailVerificationController (que buscava o usuário, validava o link e
 * salvava, tudo no controller) para o VerifyEmailUseCase. O Application
 * recebe a URL como string, não o Request do HttpFoundation.
 */
final class VerifyEmailRequest {
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
