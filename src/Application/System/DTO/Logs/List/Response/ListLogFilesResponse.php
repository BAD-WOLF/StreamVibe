<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Logs\List\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do ListLogFilesUseCase (GET /system/logs): os arquivos de log da
 * pasta de logs da aplicação, do alterado por último ao mais antigo.
 *
 *     {"directory": "/app/var/log",
 *      "files": [{"name": "prod.log", ...}, {"name": "prod.log.1", ...}]}
 *
 * <ambiente>.log é o que a aplicação escreve (FileLogger); <ambiente>.log.1
 * é o anterior, guardado quando o atual passou de 10 MiB.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ListLogFilesResponse {
    /**
     * @param string                                                                 $directory
     * @param list<\App\Application\System\DTO\Shared\Response\Model\LogFileSummary> $files
     * @param string|null                                                            $message
     */
    public function __construct(
        public private(set) string $directory {
            /**
             * @return string
             */ get => $this->directory;
        },
        public private(set) array $files {
            /**
             * @return list<\App\Application\System\DTO\Shared\Response\Model\LogFileSummary>
             */ get => $this->files;
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
     * @param string                                                                 $directory
     * @param list<\App\Application\System\DTO\Shared\Response\Model\LogFileSummary> $files
     * @param string|null                                                            $message
     *
     * @return self
     */
    public static function success(
        string $directory,
        array $files,
        ?string $message = null,
    ): self {
        return new self(
            directory: $directory,
            files: $files,
            message: $message,
        );
    }
}
