<?php

declare(strict_types = 1);

namespace App\Domain\Audit\ValueObject;

/**
 * O que uma entrada do log de auditoria registra: as ações de quem
 * administra as contas (e, pelo console, quem tem acesso ao servidor).
 *
 * O valor é o que vai para o banco e para a API (`action`); nunca mude o
 * valor de um caso que já existe, ou as entradas antigas deixam de ser
 * lidas.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
enum AuditAction: string {
    case UserVerified = 'user_verified';
    case VerificationResent = 'verification_resent';
    case UserBlocked = 'user_blocked';
    case UserUnblocked = 'user_unblocked';
    case RoleChanged = 'role_changed';
    case SessionsRevoked = 'sessions_revoked';
    case UserDeleted = 'user_deleted';

    /**
     * Os valores (para o Choice dos Request DTOs).
     *
     * @return list<string>
     */
    public static function values(): array {
        return array_map(static fn(self $action): string => $action->value, self::cases());
    }
}
