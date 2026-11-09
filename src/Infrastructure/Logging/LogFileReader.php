<?php

declare(strict_types = 1);

namespace App\Infrastructure\Logging;

use App\Application\System\DTO\Logs\Read\Response\Model\LogEntry;
use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;
use App\Application\System\Port\LogReaderInterface;
use App\Application\System\Shared\LogLevel;
use DateTimeImmutable;
use Exception;

/**
 * Implementação da LogReaderInterface: os arquivos da pasta de logs
 * (%kernel.logs_dir%, a var/log), lidos do fim para o começo.
 *
 * - Só entram arquivos comuns com nome de log (prod.log, dev.log.1,
 *   doctrine-2026-11-08.log); links simbólicos e pastas ficam de fora, e
 *   um nome com barra ou ponto no começo nunca chega a virar caminho.
 * - entries() lê blocos de CHUNK_BYTES a partir do fim do arquivo e
 *   devolve as linhas uma a uma (gerador): quem lê para quando quiser e o
 *   resto do arquivo nem é lido. Uma linha maior que MAX_LINE_BYTES é
 *   cortada (fica o começo, com a data e o nível).
 * - Linhas no formato do Monolog — o do FileLogger — viram data (com
 *   milissegundos), canal, nível e mensagem; as outras vêm só com a
 *   mensagem. Bytes que não são UTF-8 válido viram "?" (o JSON da resposta
 *   não aceitaria).
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class LogFileReader implements LogReaderInterface {
    private const string NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*\.log(\.\d+)?$/';

    private const string LINE_PATTERN = '/^\[(?<datetime>\d[^\]]*)\]\s+(?:(?<channel>[A-Za-z0-9_-]+)\.)?(?<level>[A-Za-z]+):\s?(?<message>.*)$/s';

    private const int CHUNK_BYTES = 65_536;

    private const int MAX_LINE_BYTES = 1_048_576;

    /**
     * @param string $logsDir %kernel.logs_dir%
     */
    public function __construct(
        private string $logsDir,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function directory(): string {
        return $this->logsDir;
    }

    /**
     * {@inheritDoc}
     */
    public function files(): array {
        $names = is_dir($this->logsDir) ? @scandir($this->logsDir) : false;
        $files = [];

        foreach ($names === false ? [] : $names as $name) {
            $file = $this->file($name);

            if ($file !== null) {
                $files[] = $file;
            }
        }

        usort(
            $files,
            static fn(LogFileSummary $a, LogFileSummary $b): int => [$b->modifiedAt, $a->name] <=> [$a->modifiedAt, $b->name],
        );

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function file(string $name): ?LogFileSummary {
        $path = $this->pathOf($name);

        if ($path === null) {
            return null;
        }

        clearstatcache(true, $path);
        $size = @filesize($path);
        $modifiedAt = @filemtime($path);

        if ($size === false || $modifiedAt === false) {
            return null;
        }

        return new LogFileSummary(
            name: $name,
            sizeBytes: $size,
            modifiedAt: new DateTimeImmutable('@' . $modifiedAt),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function entries(string $name): iterable {
        $path = $this->pathOf($name);
        $handle = $path === null ? false : @fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            fseek($handle, 0, SEEK_END);
            $position = (int) ftell($handle);
            // o fim de uma linha cujo começo está num bloco anterior
            $carry = '';

            while ($position > 0) {
                $length = min(self::CHUNK_BYTES, $position);
                $position -= $length;
                fseek($handle, $position);
                $chunk = fread($handle, $length);

                if ($chunk === false) {
                    break;
                }

                $lines = explode("\n", $chunk . $carry);
                $carry = substr((string) array_shift($lines), 0, self::MAX_LINE_BYTES);

                for ($index = count($lines) - 1; $index >= 0; $index--) {
                    $line = rtrim($lines[$index], "\r");

                    if ($line !== '') {
                        yield $this->parse($line);
                    }
                }
            }

            $carry = rtrim($carry, "\r");

            if ($carry !== '') {
                yield $this->parse($carry);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * O caminho de um arquivo da pasta de logs; null quando o nome não é
     * de log ou não há um arquivo comum com ele.
     *
     * @param string $name
     *
     * @return string|null
     */
    private function pathOf(string $name): ?string {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            return null;
        }

        $path = rtrim($this->logsDir, '/') . '/' . $name;

        return is_file($path) && !is_link($path) ? $path : null;
    }

    /**
     * @param string $line
     *
     * @return \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry
     */
    private function parse(string $line): LogEntry {
        $line = mb_scrub(substr($line, 0, self::MAX_LINE_BYTES), 'UTF-8');

        if (preg_match(self::LINE_PATTERN, $line, $match, PREG_UNMATCHED_AS_NULL) === 1) {
            $level = LogLevel::fromName((string) $match['level']);
            $datetime = self::datetime((string) $match['datetime']);

            if ($level !== null && $datetime !== null) {
                return new LogEntry(
                    datetime: $datetime,
                    channel: $match['channel'],
                    level: $level->value,
                    message: (string) $match['message'],
                );
            }
        }

        return new LogEntry(datetime: null, channel: null, level: null, message: $line);
    }

    /**
     * A data da linha com milissegundos ("2026-11-08T14:50:01.123+00:00");
     * null quando não é uma data.
     *
     * @param string $value
     *
     * @return string|null
     */
    private static function datetime(string $value): ?string {
        try {
            return (new DateTimeImmutable($value))->format('Y-m-d\TH:i:s.vP');
        } catch (Exception) {
            return null;
        }
    }
}
