<?php

declare(strict_types = 1);

namespace App\Domain\Exception;

use Exception;

/**
 * O usuário está autenticado, mas não tem permissão para a operação
 * (HTTP 403).
 *
 * Diferente de UserNotVerifiedException (também 403), que é sobre o
 * estado da conta, esta é sobre de quem é o recurso: ex.: um usuário
 * pedindo as solicitações de troca de senha de OUTRO usuário.
 *
 * NOTA (out/2026): criada para corrigir o IDOR em
 * GET /reset-password/requests/{userId}, que devolvia as solicitações de
 * qualquer usuário para qualquer pessoa logada. A regra ("só o próprio
 * usuário ou ROLE_ADMIN") fica no GetUserPasswordResetRequestsUseCase, e
 * esta exceção chega ao cliente pelo ExceptionListener como 403
 * {"type": "permission_denied_error"}.
 */
class PermissionDeniedException extends DomainException {
    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message = 'You do not have permission to perform this operation',
        int $code = 403,
        ?Exception $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return string
     */
    public function getErrorType(): string {
        return 'permission_denied_error';
    }

    /**
     * Um usuário tentando acessar um recurso de outro usuário.
     *
     * @param string $resource     o que foi pedido (ex.: "password reset requests")
     * @param int    $ownerId      dono do recurso
     * @param int    $requesterId  quem pediu
     *
     * @return self
     */
    public static function notOwner(string $resource, int $ownerId, int $requesterId): self {
        return new self(
            sprintf('You can only access your own %s', $resource),
            403,
            null,
            [
                'resource' => $resource,
                'owner_id' => $ownerId,
                'requester_id' => $requesterId,
            ],
        );
    }

    /**
     * NOTA (nov/2026): administração de contas — a ação é sobre a própria
     * conta do admin.
     *
     * @return self
     */
    public static function ownAccount(): self {
        return new self('You cannot do this to your own account', 403, null, ['rule' => 'own_account']);
    }

    /**
     * NOTA (nov/2026): administração de contas — o papel da conta não está
     * abaixo do papel de quem tentou.
     *
     * @param string $targetRole
     *
     * @return self
     */
    public static function higherRole(string $targetRole): self {
        return new self(
            sprintf('Only a role above %s can manage this account', $targetRole),
            403,
            null,
            ['rule' => 'higher_role', 'target_role' => $targetRole],
        );
    }

    /**
     * NOTA (nov/2026): administração de contas — o papel pedido não está
     * abaixo do papel de quem tentou dar.
     *
     * @param string $role
     *
     * @return self
     */
    public static function roleAboveYours(string $role): self {
        return new self(
            sprintf('You cannot give the role %s', $role),
            403,
            null,
            ['rule' => 'role_above_yours', 'role' => $role],
        );
    }
}
