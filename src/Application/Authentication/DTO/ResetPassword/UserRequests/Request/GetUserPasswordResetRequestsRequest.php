<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\ResetPassword\UserRequests\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetUserPasswordResetRequestsUseCase
 * (GET /reset-password/requests/{userId}): de quem são as solicitações
 * (userId) e quem está pedindo (requesterId, requesterIsAdmin — o usuário
 * do JWT).
 *
 * NOTA (out/2026): criado para fechar o IDOR da rota: antes o controller
 * passava só o userId da URL, e qualquer usuário logado via as
 * solicitações de qualquer outro. A regra "só o próprio ou ROLE_ADMIN" é
 * do UseCase; o controller só informa quem está pedindo.
 */
final class GetUserPasswordResetRequestsRequest {
    /**
     * @param int  $userId
     * @param int  $requesterId
     * @param bool $requesterIsAdmin
     */
    public function __construct(
        #[Assert\Positive(message: 'User ID must be a positive integer')]
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },

        public private(set) int $requesterId {
            /**
             * @return int
             */ get => $this->requesterId;
        },

        public private(set) bool $requesterIsAdmin = false {
            /**
             * @return bool
             */ get => $this->requesterIsAdmin;
        },
    ) {
    }
}
