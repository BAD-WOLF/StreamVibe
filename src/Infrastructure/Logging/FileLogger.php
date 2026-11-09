<?php

declare(strict_types = 1);

namespace App\Infrastructure\Logging;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Throwable;

/**
 * O logger da aplicação: decora o logger padrão do Symfony (o serviço
 * `logger`, config/services.yaml) e grava também em
 * <var/log>/<ambiente>.log — o arquivo que o painel do sistema lê
 * (GET /system/logs).
 *
 * O projeto não tem o MonologBundle, e o logger padrão do Symfony só
 * escreve no stderr (o `docker compose logs`), e só do nível error para
 * cima. Este repassa tudo a ele — o stderr e o painel de logs do profiler
 * continuam como antes — e grava no arquivo a partir de $minLevel
 * (app.log.level: info em dev e test, warning em prod; a variável LOG_LEVEL
 * troca).
 *
 * Formato do Monolog, para o arquivo ser lido por qualquer ferramenta que
 * conheça o dev.log de um projeto Symfony:
 *
 *     [2026-11-08T14:50:01.123+00:00] app.ERROR: Domain exception occurred {"status_code":404}
 *
 * - uma linha por registro: quebras de linha da mensagem viram espaço; o
 *   contexto vai no fim como JSON (exceções viram classe, mensagem,
 *   código e arquivo:linha; objetos, o nome da classe);
 * - os {placeholders} da mensagem (PSR-3) são preenchidos com o contexto;
 * - passou de MAX_FILE_BYTES, o arquivo vira <ambiente>.log.1 (o .1
 *   anterior é descartado) e um novo começa;
 * - nunca derruba a aplicação: sem permissão para criar a pasta ou o
 *   arquivo, o problema sai uma vez pelo logger padrão (stderr) e o
 *   arquivo é deixado de lado até o fim do processo.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class FileLogger extends AbstractLogger implements DebugLoggerInterface {
    public const int MAX_FILE_BYTES = 10 * 1024 * 1024;

    private const string CHANNEL = 'app';

    private const int MAX_CONTEXT_DEPTH = 5;

    private const int JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Os níveis do PSR-3, do menos ao mais grave.
     */
    private const array SEVERITY = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private int $minimumSeverity;

    /**
     * O arquivo aberto (na primeira gravação, e de novo se alguém o fechar).
     *
     * @var resource|null
     */
    private $handle = null;

    private bool $unavailable = false;

    /**
     * Gravando agora. Um aviso do PHP lá dentro (disco cheio, sem
     * permissão) volta por log() — com o debug ligado o ErrorHandler do
     * Symfony registra até os avisos silenciados com @ —, e sem esta trava
     * a gravação chamaria a si mesma sem fim.
     */
    private bool $busy = false;

    /**
     * @param \Psr\Log\LoggerInterface $inner       o logger padrão do Symfony (decorado)
     * @param string                   $logsDir     %kernel.logs_dir%
     * @param string                   $environment %kernel.environment% (o nome do arquivo)
     * @param string                   $minLevel    nível mínimo gravado no arquivo; um nome que não é do PSR-3 vale warning
     */
    public function __construct(
        private readonly LoggerInterface $inner,
        private readonly string $logsDir,
        private readonly string $environment,
        string $minLevel,
    ) {
        $this->minimumSeverity = self::SEVERITY[strtolower(trim($minLevel))] ?? self::SEVERITY[LogLevel::WARNING];
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed                $level
     * @param string|\Stringable   $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void {
        // um nível inválido faz o logger padrão lançar a exceção do PSR-3, como antes
        $this->inner->log($level, $message, $context);

        if (!is_string($level) || !isset(self::SEVERITY[$level]) || self::SEVERITY[$level] < $this->minimumSeverity) {
            return;
        }

        $this->write($this->format($level, (string) $message, $context));
    }

    /**
     * {@inheritDoc}
     */
    public function getLogs(?Request $request = null): array {
        return $this->inner instanceof DebugLoggerInterface ? $this->inner->getLogs($request) : [];
    }

    /**
     * {@inheritDoc}
     */
    public function countErrors(?Request $request = null): int {
        return $this->inner instanceof DebugLoggerInterface ? $this->inner->countErrors($request) : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void {
        if ($this->inner instanceof DebugLoggerInterface) {
            $this->inner->clear();
        }
    }

    /**
     * O caminho do arquivo de log deste ambiente.
     *
     * @return string
     */
    private function path(): string {
        return rtrim($this->logsDir, '/') . '/' . $this->environment . '.log';
    }

    /**
     * Uma linha no formato do Monolog, com a quebra de linha no fim.
     *
     * @param string               $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return string
     */
    private function format(string $level, string $message, array $context): string {
        $line = sprintf(
            '[%s] %s.%s: %s',
            (new DateTimeImmutable())->format('Y-m-d\TH:i:s.vP'),
            self::CHANNEL,
            strtoupper($level),
            str_replace(["\r\n", "\r", "\n"], ' ', self::interpolate($message, $context)),
        );

        if ($context !== []) {
            $json = json_encode(self::normalize($context, 0), self::JSON_FLAGS);

            if (is_string($json)) {
                $line .= ' ' . $json;
            }
        }

        return $line . "\n";
    }

    /**
     * Grava a linha; na primeira vez, cria a pasta, faz a rotação e abre
     * o arquivo. Uma falha desliga o arquivo até o fim do processo.
     *
     * NOTA (nov/2026): sem __destruct() que feche o arquivo — o PHP o fecha
     * sozinho quando o logger some. Na coleta de ciclos (o container antigo
     * entre as requisições do KernelBrowser) os destrutores rodam em
     * qualquer ordem, e o do Doctrine\DBAL\Logging\Connection registra
     * "Disconnecting" no logger: com o arquivo já fechado, o fwrite() lança
     * TypeError (o @ não segura exceção). Por isso também um arquivo fechado
     * por fora é aberto de novo, e nada daqui sai como exceção: um logger
     * nunca derruba a requisição.
     *
     * @param string $line
     *
     * @return void
     */
    private function write(string $line): void {
        if ($this->busy || $this->unavailable) {
            return;
        }

        $this->busy = true;

        try {
            if (!is_resource($this->handle)) {
                $this->handle = $this->open();
            }

            if ($this->handle === null || @fwrite($this->handle, $line) === false) {
                $this->unavailable = true;
            }
        } catch (Throwable) {
            $this->unavailable = true;
        } finally {
            $this->busy = false;
        }
    }

    /**
     * @return resource|null
     */
    private function open() {
        $path = $this->path();

        if (!is_dir($this->logsDir) && !@mkdir($this->logsDir, 0777, true) && !is_dir($this->logsDir)) {
            $this->inner->error('The log directory cannot be created; the file log is off', ['path' => $this->logsDir]);

            return null;
        }

        clearstatcache(true, $path);

        if (is_file($path) && (int) @filesize($path) > self::MAX_FILE_BYTES) {
            @rename($path, $path . '.1');
        }

        $handle = @fopen($path, 'ab');

        if ($handle === false) {
            $this->inner->error('The log file cannot be opened; the file log is off', ['path' => $path]);

            return null;
        }

        return $handle;
    }

    /**
     * Preenche os {placeholders} do PSR-3 com o contexto.
     *
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return string
     */
    private static function interpolate(string $message, array $context): string {
        if (!str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = match (true) {
                $value === null => '',
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                $value instanceof DateTimeInterface => $value->format(DateTimeInterface::RFC3339),
                $value instanceof Stringable => (string) $value,
                is_object($value) => '[object ' . $value::class . ']',
                default => '[' . get_debug_type($value) . ']',
            };
        }

        return strtr($message, $replacements);
    }

    /**
     * O contexto pronto para o JSON: só escalares e arrays.
     *
     * @param mixed $value
     * @param int   $depth
     *
     * @return mixed
     */
    private static function normalize(mixed $value, int $depth): mixed {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($depth >= self::MAX_CONTEXT_DEPTH) {
            return '[' . get_debug_type($value) . ']';
        }

        if (is_array($value)) {
            return array_map(static fn(mixed $item): mixed => self::normalize($item, $depth + 1), $value);
        }

        if ($value instanceof Throwable) {
            $exception = [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
                'file' => $value->getFile() . ':' . $value->getLine(),
            ];

            if ($value->getPrevious() !== null) {
                $exception['previous'] = self::normalize($value->getPrevious(), $depth + 1);
            }

            return $exception;
        }

        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d\TH:i:s.vP'),
            $value instanceof JsonSerializable => self::normalize($value->jsonSerialize(), $depth + 1),
            $value instanceof Stringable => (string) $value,
            is_object($value) => '[object ' . $value::class . ']',
            default => '[' . get_debug_type($value) . ']',
        };
    }
}
