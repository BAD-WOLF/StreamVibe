<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Register\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do RegisterUserUseCase: o `data` de POST /register.
 *
 *     {"user_id": 42}
 *
 * O `user_id` é o que o cliente usa para consultar
 * GET /verify/email/status/{userId} enquanto espera a verificação.
 *
 * NOTA (out/2026):
 * - Movido de DTO/Register/ para DTO/Register/Response/.
 * - Saíram `emailSent` (sempre true: se o email não sai, o UseCase lança
 *   EmailDeliveryException e a resposta é 503), `verificationUrl` (nunca
 *   preenchido — e o link de verificação não pode ir na resposta, senão
 *   qualquer um verificaria um email que não é seu), emailResent(),
 *   isSuccess() (sempre true) e os getters de compatibilidade.
 * - `message` é envelope: o RegisterUserOutput a expõe no nível de cima.
 */
final class RegisterUserResponse {
    /**
     * @param int         $userId
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('user_id')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
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
     * @param string|null $message
     *
     * @return self
     */
    public static function success(int $userId, ?string $message = null): self {
        return new self(userId: $userId, message: $message);
    }
}
