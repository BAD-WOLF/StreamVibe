<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\EmailVerification\ValidateLink\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ValidateEmailVerificationLinkUseCase: o `data` de
 * GET /verify/email/validate.
 *
 *     {"user_id": 42, "valid": true, "already_verified": false}
 *
 * Link inválido ou expirado NÃO é erro aqui: volta 200 com valid=false
 * (é uma consulta). Só usuário inexistente (404) e parâmetros inválidos
 * (400) saem como erro.
 *
 * NOTA (out/2026): o controller montava esse JSON na mão, com a
 * `message` repetida dentro de `data`; agora `message` fica só no
 * envelope.
 */
final class ValidateEmailVerificationLinkResponse {
    /**
     * @param int         $userId
     * @param bool        $valid
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
        public private(set) bool $valid {
            /**
             * @return bool
             */ get => $this->valid;
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
     * @param int         $userId
     * @param bool        $valid
     * @param bool        $alreadyVerified
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $userId,
        bool $valid,
        bool $alreadyVerified,
        ?string $message = null,
    ): self {
        return new self(
            userId: $userId,
            valid: $valid,
            alreadyVerified: $alreadyVerified,
            message: $message,
        );
    }
}
