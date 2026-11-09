<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Logs\Read\Response\Model;

/**
 * Uma linha de um arquivo de log (`data.entries` de GET /system/logs/{file}):
 *
 *     {"datetime": "2026-11-08T14:50:01.123+00:00", "channel": "app", "level": "error",
 *      "message": "Domain exception occurred {\"exception_class\":\"...\"}"}
 *
 * - a linha é lida no formato do Monolog ("[data] canal.NÍVEL: mensagem"),
 *   que é o que a aplicação escreve; numa linha em outro formato,
 *   datetime, channel e level vêm null e message traz a linha inteira;
 * - datetime com milissegundos; level em minúsculas (PSR-3);
 * - message traz o contexto (JSON) no fim, com os segredos trocados por
 *   "***", e é cortada em 4.000 caracteres (com "…" no fim).
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class LogEntry {
    /**
     * @param string|null $datetime
     * @param string|null $channel
     * @param string|null $level
     * @param string      $message
     */
    public function __construct(
        public private(set) ?string $datetime {
            /**
             * @return string|null
             */ get => $this->datetime;
        },
        public private(set) ?string $channel {
            /**
             * @return string|null
             */ get => $this->channel;
        },
        public private(set) ?string $level {
            /**
             * @return string|null
             */ get => $this->level;
        },
        public private(set) string $message {
            /**
             * @return string
             */ get => $this->message;
        },
    ) {
    }

    /**
     * A mesma linha com outra mensagem (o UseCase troca os segredos
     * dela por "***" e a corta no tamanho máximo).
     *
     * @param string $message
     *
     * @return self
     */
    public function withMessage(string $message): self {
        return new self(
            datetime: $this->datetime,
            channel: $this->channel,
            level: $this->level,
            message: $message,
        );
    }
}
