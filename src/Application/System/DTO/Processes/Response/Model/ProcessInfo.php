<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Processes\Response\Model;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Um processo do contêiner (`data.processes` de GET /system/processes):
 *
 *     {"pid": 1, "parent_pid": 0, "name": "php-fpm",
 *      "command": "php-fpm: master process (/usr/local/etc/php-fpm.conf)",
 *      "state": "sleeping", "user": "root", "memory_bytes": 31457280,
 *      "cpu_seconds": 12.5, "threads": 1, "started_at": "2026-11-08T12:00:00+00:00"}
 *
 * - command: a linha de comando, com os segredos trocados por "***"; um
 *   processo sem linha de comando (zumbi) traz o nome entre colchetes;
 * - state: running, sleeping, waiting (esperando o disco), stopped, zombie,
 *   idle, dead ou unknown;
 * - user: o nome do usuário (/etc/passwd) ou, sem ele, o uid;
 * - memory_bytes: a memória residente (RSS);
 * - cpu_seconds: o tempo de CPU desde que o processo começou (usuário +
 *   sistema); dividido pelo tempo de vida, dá o uso médio;
 * - started_at: null quando não dá para calcular.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ProcessInfo {
    /**
     * @param int                     $pid
     * @param int                     $parentPid
     * @param string                  $name
     * @param string                  $command
     * @param string                  $state
     * @param string                  $user
     * @param int                     $memoryBytes
     * @param float                   $cpuSeconds
     * @param int                     $threads
     * @param \DateTimeImmutable|null $startedAt
     */
    public function __construct(
        public private(set) int $pid {
            /**
             * @return int
             */ get => $this->pid;
        },
        #[SerializedName('parent_pid')]
        public private(set) int $parentPid {
            /**
             * @return int
             */ get => $this->parentPid;
        },
        public private(set) string $name {
            /**
             * @return string
             */ get => $this->name;
        },
        public private(set) string $command {
            /**
             * @return string
             */ get => $this->command;
        },
        public private(set) string $state {
            /**
             * @return string
             */ get => $this->state;
        },
        public private(set) string $user {
            /**
             * @return string
             */ get => $this->user;
        },
        #[SerializedName('memory_bytes')]
        public private(set) int $memoryBytes {
            /**
             * @return int
             */ get => $this->memoryBytes;
        },
        #[SerializedName('cpu_seconds')]
        public private(set) float $cpuSeconds {
            /**
             * @return float
             */ get => $this->cpuSeconds;
        },
        public private(set) int $threads {
            /**
             * @return int
             */ get => $this->threads;
        },
        #[SerializedName('started_at')]
        public private(set) ?DateTimeImmutable $startedAt {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->startedAt;
        },
    ) {
    }

    /**
     * O mesmo processo com outra linha de comando (o UseCase troca
     * os segredos dela por "***").
     *
     * @param string $command
     *
     * @return self
     */
    public function withCommand(string $command): self {
        return new self(
            pid: $this->pid,
            parentPid: $this->parentPid,
            name: $this->name,
            command: $command,
            state: $this->state,
            user: $this->user,
            memoryBytes: $this->memoryBytes,
            cpuSeconds: $this->cpuSeconds,
            threads: $this->threads,
            startedAt: $this->startedAt,
        );
    }
}
