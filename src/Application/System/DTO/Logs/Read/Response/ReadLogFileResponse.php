<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Logs\Read\Response;

use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do ReadLogFileUseCase (GET /system/logs/{file}): as últimas
 * linhas do arquivo que passam nos filtros, da mais nova para a mais
 * antiga.
 *
 *     {"file": {"name": "prod.log", "size_bytes": 52341, "modified_at": "..."},
 *      "entries": [{"datetime": "...", "level": "error", ...}],
 *      "scanned_lines": 812, "complete": true}
 *
 * - file: o arquivo no momento da leitura (como na lista de GET /system/logs);
 * - scanned_lines: quantas linhas foram lidas, do fim para o começo, até
 *   juntar as entries;
 * - complete: o arquivo foi lido até a primeira linha. false = há linhas
 *   mais antigas que não foram lidas: a leitura para quando junta `limit`
 *   linhas ou depois de ler 50.000.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ReadLogFileResponse {
    /**
     * @param \App\Application\System\DTO\Shared\Response\Model\LogFileSummary    $file
     * @param list<\App\Application\System\DTO\Logs\Read\Response\Model\LogEntry> $entries
     * @param int                                                                 $scannedLines
     * @param bool                                                                $complete
     * @param string|null                                                         $message
     */
    public function __construct(
        public private(set) LogFileSummary $file {
            /**
             * @return \App\Application\System\DTO\Shared\Response\Model\LogFileSummary
             */ get => $this->file;
        },
        public private(set) array $entries {
            /**
             * @return list<\App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>
             */ get => $this->entries;
        },
        #[SerializedName('scanned_lines')]
        public private(set) int $scannedLines {
            /**
             * @return int
             */ get => $this->scannedLines;
        },
        public private(set) bool $complete {
            /**
             * @return bool
             */ get => $this->complete;
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
     * @param \App\Application\System\DTO\Shared\Response\Model\LogFileSummary    $file
     * @param list<\App\Application\System\DTO\Logs\Read\Response\Model\LogEntry> $entries
     * @param int                                                                 $scannedLines
     * @param bool                                                                $complete
     * @param string|null                                                         $message
     *
     * @return self
     */
    public static function success(
        LogFileSummary $file,
        array $entries,
        int $scannedLines,
        bool $complete,
        ?string $message = null,
    ): self {
        return new self(
            file: $file,
            entries: $entries,
            scannedLines: $scannedLines,
            complete: $complete,
            message: $message,
        );
    }
}
