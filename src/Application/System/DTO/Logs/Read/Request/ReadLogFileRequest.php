<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Logs\Read\Request;

use App\Application\System\Shared\LogLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do ReadLogFileUseCase (GET /system/logs/{file}): o arquivo (da
 * URL) e os filtros da query string.
 *
 * - file: o nome do arquivo, como na lista de GET /system/logs (só o
 *   nome: barra e ponto no começo não passam, então nada fora da pasta de
 *   logs pode ser pedido);
 * - limit: quantas linhas, no máximo (1 a 1000);
 * - level: só as linhas deste nível ou mais graves (um dos níveis do
 *   PSR-3, LogLevel); null = todas, inclusive as que não têm nível;
 * - search: só as linhas cuja mensagem contém este texto, sem diferenciar
 *   maiúsculas; null ou vazio = sem busca.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ReadLogFileRequest {
    /**
     * @param string      $file
     * @param int         $limit
     * @param string|null $level
     * @param string|null $search
     */
    public function __construct(
        #[Assert\NotBlank(message: 'File is required')]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', message: 'File must be the name of a log file')]
        public private(set) string $file {
            /**
             * @return string
             */ get => $this->file;
        },
        #[Assert\Range(notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}', min: 1, max: 1000)]
        public private(set) int $limit = 200 {
            /**
             * @return int
             */ get => $this->limit;
        },
        #[Assert\Choice(callback: [LogLevel::class, 'values'], message: 'Level must be one of: debug, info, notice, warning, error, critical, alert, emergency')]
        public private(set) ?string $level = null {
            /**
             * @return string|null
             */ get => $this->level;
        },
        #[Assert\Length(max: 200, maxMessage: 'Search must have at most {{ limit }} characters')]
        public private(set) ?string $search = null {
            /**
             * @return string|null
             */ get => $this->search;
        },
    ) {
    }
}
