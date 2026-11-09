<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Processes\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do ListProcessesUseCase (GET /system/processes): os processos
 * do contêiner, em ordem de PID.
 *
 *     {"processes": [{"pid": 1, "name": "php-fpm", "state": "sleeping", ...}, ...]}
 *
 * No Docker o contêiner tem o próprio espaço de PIDs: o PID 1 é o processo
 * principal dele (php-fpm em produção, o servidor do Symfony CLI em dev), e
 * os da máquina não aparecem.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ListProcessesResponse {
    /**
     * @param list<\App\Application\System\DTO\Processes\Response\Model\ProcessInfo> $processes
     * @param string|null                                                            $message
     */
    public function __construct(
        public private(set) array $processes {
            /**
             * @return list<\App\Application\System\DTO\Processes\Response\Model\ProcessInfo>
             */ get => $this->processes;
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
     * @param list<\App\Application\System\DTO\Processes\Response\Model\ProcessInfo> $processes
     * @param string|null                                                            $message
     *
     * @return self
     */
    public static function success(
        array $processes,
        ?string $message = null,
    ): self {
        return new self(
            processes: $processes,
            message: $message,
        );
    }
}
