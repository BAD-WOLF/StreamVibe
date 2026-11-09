<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Logging;

use App\Infrastructure\Logging\FileLogger;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use RuntimeException;
use stdClass;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Log\Logger;

/**
 * FileLogger: repassa tudo ao logger padrão e grava em
 * <pasta>/<ambiente>.log, no formato do Monolog, a partir do nível
 * mínimo; contexto em JSON, placeholders do PSR-3, uma linha por registro,
 * rotação em MAX_FILE_BYTES e nenhuma exceção quando o arquivo não pode
 * ser escrito.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class FileLoggerTest extends TestCase
{
    private string $directory;

    /**
     * @var list<array{string, string, array<string, mixed>}>
     */
    private array $forwarded = [];

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/file-logger-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_file($this->directory)) {
            unlink($this->directory);
        }

        if (!is_dir($this->directory)) {
            return;
        }

        foreach (scandir($this->directory) ?: [] as $name) {
            if (is_file("{$this->directory}/{$name}")) {
                unlink("{$this->directory}/{$name}");
            }
        }

        rmdir($this->directory);
    }

    public function testItWritesTheMonologFormatFromTheMinimumLevel(): void
    {
        $logger = $this->logger('warning');

        $logger->info('not in the file');
        $logger->warning('Slow query');
        $logger->error('Domain exception occurred', ['status_code' => 404]);
        $lines = $this->lines();

        self::assertCount(2, $lines);
        self::assertMatchesRegularExpression('/^\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+-]\d{2}:\d{2}\] app\.WARNING: Slow query$/', $lines[0]);
        self::assertStringEndsWith('] app.ERROR: Domain exception occurred {"status_code":404}', $lines[1]);
    }

    public function testEverythingStillGoesToTheDecoratedLogger(): void
    {
        $logger = $this->logger('error');

        $logger->debug('a debug line', ['a' => 1]);
        $logger->critical('a critical line');

        self::assertSame([['debug', 'a debug line', ['a' => 1]], ['critical', 'a critical line', []]], $this->forwarded);
        self::assertCount(1, $this->lines());
    }

    public function testPlaceholdersAndLineBreaks(): void
    {
        $this->logger('debug')->info("Matched route \"{route}\".\nNext {missing} {flag} {none}", ['route' => 'get_system_server', 'flag' => false, 'none' => null]);

        self::assertStringEndsWith(
            '] app.INFO: Matched route "get_system_server". Next {missing} false  {"route":"get_system_server","flag":false,"none":null}',
            $this->lines()[0],
        );
    }

    public function testTheContextBecomesJson(): void
    {
        $exception = new RuntimeException('boom', 3, new LogicException('cause'));
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'as text';
            }
        };

        $this->logger('debug')->error('Failed', [
            'exception' => $exception,
            'when' => new DateTimeImmutable('2026-11-08T10:00:00.5+00:00'),
            'object' => new stdClass(),
            'stringable' => $stringable,
            'path' => '/app/var/log',
            'deep' => ['a' => ['b' => ['c' => ['d' => ['e' => 'f']]]]],
        ]);
        $json = substr($this->lines()[0], (int) strpos($this->lines()[0], '{'));
        $context = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(['class', 'message', 'code', 'file', 'previous'], array_keys($context['exception']));
        self::assertSame(['RuntimeException', 'boom', 3], [$context['exception']['class'], $context['exception']['message'], $context['exception']['code']]);
        self::assertSame('LogicException', $context['exception']['previous']['class']);
        self::assertStringContainsString(__FILE__ . ':', $context['exception']['file']);
        self::assertSame('2026-11-08T10:00:00.500+00:00', $context['when']);
        self::assertSame('[object stdClass]', $context['object']);
        self::assertSame('as text', $context['stringable']);
        self::assertSame('/app/var/log', $context['path'], 'slashes are not escaped');
        self::assertSame(['a' => ['b' => ['c' => ['d' => '[array]']]]], $context['deep']);
    }

    public function testAnInvalidLevelStillFailsAsBefore(): void
    {
        $logger = new FileLogger(new Logger('debug', fopen('php://memory', 'w')), $this->directory, 'dev', 'debug');

        $this->expectException(InvalidArgumentException::class);

        $logger->log('loud', 'x');
    }

    public function testAnUnknownMinimumLevelMeansWarning(): void
    {
        $logger = $this->logger('verbose');

        $logger->notice('left out');
        $logger->warning('kept');

        self::assertCount(1, $this->lines());
    }

    public function testAFullFileIsRotated(): void
    {
        mkdir($this->directory);
        file_put_contents($this->directory . '/dev.log', str_repeat('x', FileLogger::MAX_FILE_BYTES + 1));
        file_put_contents($this->directory . '/dev.log.1', 'the previous one is discarded');

        $this->logger('debug')->info('fresh start');

        self::assertSame(FileLogger::MAX_FILE_BYTES + 1, filesize($this->directory . '/dev.log.1'));
        self::assertCount(1, $this->lines());
    }

    /**
     * Um arquivo no lugar da pasta: a pasta não pode ser criada (vale
     * também para quem roda como root, como nos contêineres).
     */
    public function testAFolderThatCannotBeCreatedDoesNotBreakTheApplication(): void
    {
        file_put_contents($this->directory, 'a file where the folder should be');
        $logger = $this->logger('debug');

        $logger->error('first');
        $logger->error('second');

        // os dois registros e o aviso — uma vez só — pelo logger padrão
        self::assertSame(
            ['first', 'The log directory cannot be created; the file log is off', 'second'],
            array_column($this->forwarded, 1),
        );
        self::assertSame(['error', 'error', 'error'], array_column($this->forwarded, 0));
    }

    /**
     * Um aviso do PHP durante a gravação volta por log() — com o debug
     * ligado o ErrorHandler do Symfony registra até os silenciados com @.
     * O registro que volta vai para o logger padrão, mas não tenta gravar
     * de novo (sem a trava, seria uma chamada sem fim).
     */
    public function testALogCallDuringTheWriteIsNotWrittenAgain(): void
    {
        $inner = new class extends AbstractLogger {
            public ?FileLogger $logger = null;

            /**
             * @var list<string>
             */
            public array $messages = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->messages[] = (string) $message;

                if (str_starts_with((string) $message, 'The log directory')) {
                    // o que o ErrorHandler faria com o aviso do mkdir()
                    $this->logger?->warning('mkdir(): File exists');
                }
            }
        };
        file_put_contents($this->directory, 'a file where the folder should be');
        $logger = new FileLogger($inner, $this->directory, 'dev', 'debug');
        $inner->logger = $logger;

        $logger->error('first');
        $logger->error('second');

        self::assertSame(
            ['first', 'The log directory cannot be created; the file log is off', 'mkdir(): File exists', 'second'],
            $inner->messages,
        );
    }

    public function testTheProfilerKeepsTheLogsOfTheDecoratedLogger(): void
    {
        $request = new Request();
        $stack = new RequestStack();
        $stack->push($request);
        $inner = new Logger('debug', fopen('php://memory', 'w'), null, $stack, true);
        $logger = new FileLogger($inner, $this->directory, 'dev', 'error');

        $logger->error('kept for the profiler');

        self::assertCount(1, $logger->getLogs($request));
        self::assertSame(1, $logger->countErrors($request));
        $logger->clear();
        self::assertSame([], $logger->getLogs());
    }

    private function logger(string $minLevel): FileLogger
    {
        $inner = new class($this) extends AbstractLogger {
            public function __construct(private readonly FileLoggerTest $test)
            {
            }

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->test->forward((string) $level, (string) $message, $context);
            }
        };

        return new FileLogger($inner, $this->directory, 'dev', $minLevel);
    }

    /**
     * Chamado pelo logger decorado falso.
     *
     * @param array<string, mixed> $context
     */
    public function forward(string $level, string $message, array $context): void
    {
        $this->forwarded[] = [$level, $message, $context];
    }

    /**
     * @return list<string>
     */
    private function lines(): array
    {
        $path = $this->directory . '/dev.log';
        clearstatcache();

        return is_file($path) ? array_values(array_filter(explode("\n", (string) file_get_contents($path)), static fn(string $line): bool => $line !== '')) : [];
    }
}
