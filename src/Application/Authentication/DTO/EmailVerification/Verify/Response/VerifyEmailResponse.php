<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\Verify\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do VerifyEmailUseCase: o `data` de GET /verify/email.
 *
 *     {"user_id": 42, "already_verified": false, "verified": true}
 *
 * `verified` é sempre true no sucesso (link inválido sai como erro 400);
 * `already_verified` diz se o email já estava verificado antes deste
 * clique (o link é aceito de novo sem erro).
 *
 * NOTA (out/2026): o EmailVerificationController montava esse JSON na mão
 * (com um "TODO: Create EmailVerificationResponse DTO"); agora é o DTO do
 * UseCase, com os mesmos campos.
 */
final class VerifyEmailResponse {
    /**
     * @param int         $userId
     * @param bool        $alreadyVerified
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        #[SerializedName('already_verified')]
        public private(set) bool $alreadyVerified {
            /**
             * @return bool
             */ get => $this->alreadyVerified;
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
     * Sempre true: este DTO só existe quando o email está verificado.
     */
    public bool $verified {
        /**
         * @return bool
         */
        get => true;
    }

    /**
     * @param int         $userId
     * @param bool        $alreadyVerified
     * @param string|null $message
     *
     * @return self
     */
    public static function success(int $userId, bool $alreadyVerified, ?string $message = null): self {
        return new self(userId: $userId, alreadyVerified: $alreadyVerified, message: $message);
    }
}
