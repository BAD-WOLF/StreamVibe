<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\Shared\Response\Model;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Uma conta na lista da administração (`data.users` de GET /admin/users):
 *
 *     {"id": 42, "email": "ana@example.com", "role": "ROLE_USER", "verified": true,
 *      "created_at": "2026-11-08T12:00:00+00:00", "last_login_at": null, "blocked_at": null}
 *
 * - role: o papel da conta na hierarquia (o mais alto), sem os herdados;
 * - created_at: null nas contas criadas antes de a data ser registrada;
 * - last_login_at: o último login com email e senha (null = nunca);
 * - blocked_at: quando foi bloqueada (null = liberada).
 *
 * Os mesmos campos abrem o detalhe da conta (GetUserDetailsResponse), que
 * acrescenta o resto.
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
final class AdminUserSummary {
    /**
     * @param int                     $id
     * @param string                  $email
     * @param string                  $role
     * @param bool                    $verified
     * @param \DateTimeImmutable|null $createdAt
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param \DateTimeImmutable|null $blockedAt
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
    ) {
    }
}
