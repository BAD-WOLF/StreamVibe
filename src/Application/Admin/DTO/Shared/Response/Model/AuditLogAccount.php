<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\Shared\Response\Model;

/**
 * Uma conta numa entrada do log de auditoria (`actor` e `target`):
 *
 *     {"id": 42, "email": "ana@example.com"}
 *
 * O id e o email da conta no momento da ação: continuam aí depois que a
 * conta for excluída (o id deixa de existir; o email pode ter sido usado
 * por outra conta depois).
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
final class AuditLogAccount {
    /**
     * @param int    $id
     * @param string $email
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
    ) {
    }
}
