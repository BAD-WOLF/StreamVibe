<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\User\Details\Response;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetUserDetailsUseCase (GET /admin/users/{userId}): tudo o
 * que a aplicação sabe da conta.
 *
 *     {
 *       "id": 42, "email": "ana@example.com", "role": "ROLE_USER", "verified": true,
 *       "created_at": "2026-11-08T12:00:00+00:00", "last_login_at": null,
 *       "blocked_at": null, "blocked_reason": null,
 *       "active_sessions": 1,
 *       "password_reset_requests": [{"id": 7, "created_at": "...", "expires_at": "..."}],
 *       "can_manage": true,
 *       "assignable_roles": ["ROLE_USER", "ROLE_DEV"]
 *     }
 *
 * - os campos da lista (AdminUserSummary), mais blocked_reason;
 * - active_sessions: sessões que ainda valem (refresh tokens não vencidos);
 * - password_reset_requests: os pedidos de troca de senha ainda válidos,
 *   no formato de GET /reset-password/requests/{userId};
 * - can_manage / assignable_roles: o que QUEM PEDIU pode fazer com esta
 *   conta (UserAdministrationPolicy) — o front mostra só as ações
 *   permitidas. O histórico da conta é o GET /admin/audit-log?user_id=.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class GetUserDetailsResponse {
    /**
     * @param int                                                                                                             $id
     * @param string                                                                                                          $email
     * @param string                                                                                                          $role
     * @param bool                                                                                                            $verified
     * @param \DateTimeImmutable|null                                                                                         $createdAt
     * @param \DateTimeImmutable|null                                                                                         $lastLoginAt
     * @param \DateTimeImmutable|null                                                                                         $blockedAt
     * @param string|null                                                                                                     $blockedReason
     * @param int                                                                                                             $activeSessions
     * @param list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary> $passwordResetRequests
     * @param bool                                                                                                            $canManage
     * @param list<string>                                                                                                    $assignableRoles
     * @param string|null                                                                                                     $message
     */
    public function __construct(
        public private(set) int $id {
            /**
             * @return int
             */ get => $this->id;
        },
        public private(set) string $email {
            /**
             * @return string
             */ get => $this->email;
        },
        public private(set) string $role {
            /**
             * @return string
             */ get => $this->role;
        },
        public private(set) bool $verified {
            /**
             * @return bool
             */ get => $this->verified;
        },
        #[SerializedName('created_at')]
        public private(set) ?DateTimeImmutable $createdAt {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->createdAt;
        },
        #[SerializedName('last_login_at')]
        public private(set) ?DateTimeImmutable $lastLoginAt {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->lastLoginAt;
        },
        #[SerializedName('blocked_at')]
        public private(set) ?DateTimeImmutable $blockedAt {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->blockedAt;
        },
        #[SerializedName('blocked_reason')]
        public private(set) ?string $blockedReason {
            /**
             * @return string|null
             */ get => $this->blockedReason;
        },
        #[SerializedName('active_sessions')]
        public private(set) int $activeSessions {
            /**
             * @return int
             */ get => $this->activeSessions;
        },
        #[SerializedName('password_reset_requests')]
        public private(set) array $passwordResetRequests {
            /**
             * @return list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary>
             */ get => $this->passwordResetRequests;
        },
        #[SerializedName('can_manage')]
        public private(set) bool $canManage {
            /**
             * @return bool
             */ get => $this->canManage;
        },
        #[SerializedName('assignable_roles')]
        public private(set) array $assignableRoles {
            /**
             * @return list<string>
             */ get => $this->assignableRoles;
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
     * @param int                                                                                                             $id
     * @param string                                                                                                          $email
     * @param string                                                                                                          $role
     * @param bool                                                                                                            $verified
     * @param \DateTimeImmutable|null                                                                                         $createdAt
     * @param \DateTimeImmutable|null                                                                                         $lastLoginAt
     * @param \DateTimeImmutable|null                                                                                         $blockedAt
     * @param string|null                                                                                                     $blockedReason
     * @param int                                                                                                             $activeSessions
     * @param list<\App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary> $passwordResetRequests
     * @param bool                                                                                                            $canManage
     * @param list<string>                                                                                                    $assignableRoles
     * @param string|null                                                                                                     $message
     *
     * @return self
     */
    public static function success(
        int $id,
        string $email,
        string $role,
        bool $verified,
        ?DateTimeImmutable $createdAt,
        ?DateTimeImmutable $lastLoginAt,
        ?DateTimeImmutable $blockedAt,
        ?string $blockedReason,
        int $activeSessions,
        array $passwordResetRequests,
        bool $canManage,
        array $assignableRoles,
        ?string $message = null,
    ): self {
        return new self(id: $id, email: $email, role: $role, verified: $verified, createdAt: $createdAt, lastLoginAt: $lastLoginAt, blockedAt: $blockedAt, blockedReason: $blockedReason, activeSessions: $activeSessions, passwordResetRequests: $passwordResetRequests, canManage: $canManage, assignableRoles: $assignableRoles, message: $message);
    }
}
