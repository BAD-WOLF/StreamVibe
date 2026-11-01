<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\Status\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetEmailVerificationStatusUseCase: o `data` de
 * GET /verify/email/status/{userId}.
 *
 *     {"user_id": 42, "verified": false}
 *
 * NOTA (out/2026): a rota é pública (quem espera a verificação ainda não
 * consegue logar), e antes devolvia também o EMAIL do usuário — qualquer
 * pessoa percorria os ids e coletava os emails cadastrados. O email saiu,
 * junto com `verification_required` (era só !is_verified). `is_verified`
 * virou `verified`, o mesmo nome usado em GET /verify/email.
 */
final class GetEmailVerificationStatusResponse {
    /**
     * @param int         $userId
     * @param bool        $verified
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        public private(set) bool $verified {
            /**
             * @return bool
             */ get => $this->verified;
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
     * @param int         $userId
     * @param bool        $verified
     * @param string|null $message
     *
     * @return self
     */
    public static function success(int $userId, bool $verified, ?string $message = null): self {
        return new self(userId: $userId, verified: $verified, message: $message);
    }
}
