<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\System\UseCase;

use App\Application\Shared\Port\SecretMaskerInterface;
use App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus;
use App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus;
use App\Application\System\DTO\Dependencies\Response\Model\MigrationStatus;
use App\Application\System\DTO\Logs\Read\Response\Model\LogEntry;
use App\Application\System\DTO\Processes\Response\Model\ProcessInfo;
use App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo;
use App\Application\System\DTO\Runtime\Response\Model\PhpInfo;
use App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo;
use App\Application\System\DTO\Server\Response\Model\ContainerInfo;
use App\Application\System\DTO\Server\Response\Model\DiskInfo;
use App\Application\System\DTO\Server\Response\Model\HostInfo;
use App\Application\System\DTO\Server\Response\Model\MemoryInfo;
use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;
use App\Application\System\Port\DatabaseInspectorInterface;
use App\Application\System\Port\LogReaderInterface;
use App\Application\System\Port\MovieDatabaseProbeInterface;
use App\Application\System\Port\ProcessInspectorInterface;
use App\Application\System\Port\RuntimeInspectorInterface;
use App\Application\System\Port\ServerInspectorInterface;
use App\Application\System\UseCase\Dependencies\GetDependenciesStatusUseCase;
use App\Application\System\UseCase\Logs\List\ListLogFilesUseCase;
use App\Application\System\UseCase\Processes\ListProcessesUseCase;
use App\Application\System\UseCase\Runtime\GetRuntimeStatusUseCase;
use App\Application\System\UseCase\Server\GetServerStatusUseCase;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * Os UseCases do painel do sistema que só leem e devolvem (servidor,
 * runtime, dependências, processos e a lista de logs): a resposta é o que
 * as portas leram, e o texto que vem de fora (erros do banco e do TMDB,
 * linhas de comando) sai com os segredos trocados por "***".
 *
 * As portas são falsas, em memória; o mascarador falso troca "hunter2".
 * O ReadLogFileUseCase tem o próprio teste.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class SystemStatusUseCasesTest extends TestCase
{
    public function testTheServerIsWhatTheInspectorRead(): void
    {
        $host = new HostInfo(
            hostname: 'app',
            os: 'Debian GNU/Linux 12 (bookworm)',
            kernel: '6.6.87.2-microsoft-standard-WSL2',
            architecture: 'x86_64',
            cpus: 8,
            uptimeSeconds: 3600,
            loadAverage: [0.52, 0.48, 0.4],
            memory: new MemoryInfo(totalBytes: 8_000, availableBytes: 3_000, swapTotalBytes: 2_000, swapFreeBytes: 2_000),
        );
        $container = new ContainerInfo(cgroupVersion: 2, memoryLimitBytes: null, memoryUsageBytes: 512, cpuLimit: 1.5, processesLimit: null, processes: 7);
        $disks = [new DiskInfo(mountPoint: '/', filesystem: 'overlay', totalBytes: 100, freeBytes: 40)];

        $server = new class($host, $container, $disks) implements ServerInspectorInterface {
            /**
             * @param list<\App\Application\System\DTO\Server\Response\Model\DiskInfo> $disks
             */
            public function __construct(private HostInfo $host, private ?ContainerInfo $container, private array $disks)
            {
            }

            public function host(): HostInfo
            {
                return $this->host;
            }

            public function container(): ?ContainerInfo
            {
                return $this->container;
            }

            public function disks(): array
            {
                return $this->disks;
            }
        };

        $response = new GetServerStatusUseCase($server, new IdentityTranslator())->execute();

        self::assertSame($host, $response->host);
        self::assertSame($container, $response->container);
        self::assertSame($disks, $response->disks);
        self::assertSame('Server status retrieved successfully', $response->message);
    }

    public function testTheRuntimeIsWhatTheInspectorRead(): void
    {
        $php = new PhpInfo(
            version: '8.4.14',
            sapi: 'fpm-fcgi',
            iniFile: null,
            memoryLimitBytes: 134217728,
            maxExecutionTimeSeconds: 30,
            uploadMaxFilesizeBytes: 2097152,
            postMaxSizeBytes: null,
            timezone: 'UTC',
            extensions: ['Core', 'pdo_pgsql'],
        );
        $symfony = new SymfonyInfo(version: '7.4.16', environment: 'prod', debug: false, endOfMaintenance: '2028-11', endOfLife: '2029-11', bundles: ['FrameworkBundle']);

        $runtime = new class($php, $symfony) implements RuntimeInspectorInterface {
            public function __construct(private PhpInfo $php, private SymfonyInfo $symfony)
            {
            }

            public function php(): PhpInfo
            {
                return $this->php;
            }

            public function opcache(): ?OpcacheInfo
            {
                return null;
            }

            public function symfony(): SymfonyInfo
            {
                return $this->symfony;
            }
        };

        $response = new GetRuntimeStatusUseCase($runtime, new IdentityTranslator())->execute();

        self::assertSame($php, $response->php);
        self::assertNull($response->opcache);
        self::assertSame($symfony, $response->symfony);
        self::assertSame('Runtime status retrieved successfully', $response->message);
    }

    public function testTheErrorsOfTheDependenciesLoseTheirSecrets(): void
    {
        $database = new DatabaseStatus(
            reachable: false,
            latencyMs: null,
            error: 'connection to postgresql://app:hunter2@database failed',
            version: null,
            sizeBytes: null,
            connections: null,
            migrations: null,
        );
        $tmdb = new ExternalServiceStatus(reachable: true, latencyMs: 180.5, statusCode: 401, error: 'Bearer hunter2 was rejected');

        $response = $this->dependencies($database, $tmdb)->execute();

        self::assertSame('connection to postgresql://app:***@database failed', $response->database->error);
        self::assertFalse($response->database->reachable);
        self::assertSame('Bearer *** was rejected', $response->tmdb->error);
        self::assertSame([true, 180.5, 401], [$response->tmdb->reachable, $response->tmdb->latencyMs, $response->tmdb->statusCode]);
        self::assertSame('Dependencies checked successfully', $response->message);
    }

    public function testHealthyDependenciesComeAsTheyAre(): void
    {
        $database = new DatabaseStatus(
            reachable: true,
            latencyMs: 0.84,
            error: null,
            version: '16.4',
            sizeBytes: 8_053_091,
            connections: 3,
            migrations: new MigrationStatus(executed: 2, pending: 0, current: 'DoctrineMigrations\\Version20261108120000'),
        );
        $tmdb = new ExternalServiceStatus(reachable: true, latencyMs: 182.4, statusCode: 200, error: null);

        $response = $this->dependencies($database, $tmdb)->execute();

        self::assertSame($database, $response->database);
        self::assertSame($tmdb, $response->tmdb);
    }

    public function testTheCommandLinesLoseTheirSecrets(): void
    {
        $started = new DateTimeImmutable('2026-11-08 12:00:00');
        $processes = new class([
            new ProcessInfo(pid: 1, parentPid: 0, name: 'php-fpm', command: 'php-fpm: master process', state: 'sleeping', user: 'root', memoryBytes: 1024, cpuSeconds: 1.5, threads: 1, startedAt: $started),
            new ProcessInfo(pid: 9, parentPid: 1, name: 'php', command: 'php worker.php --password=hunter2', state: 'running', user: 'www-data', memoryBytes: 2048, cpuSeconds: 0.25, threads: 2, startedAt: null),
        ]) implements ProcessInspectorInterface {
            /**
             * @param list<\App\Application\System\DTO\Processes\Response\Model\ProcessInfo> $processes
             */
            public function __construct(private array $processes)
            {
            }

            public function processes(): array
            {
                return $this->processes;
            }
        };

        $response = new ListProcessesUseCase($processes, $this->masker(), new IdentityTranslator())->execute();

        self::assertCount(2, $response->processes);
        [$master, $worker] = $response->processes;
        self::assertSame('php-fpm: master process', $master->command);
        self::assertSame('php worker.php --password=***', $worker->command);
        // o resto do processo não muda
        self::assertSame([9, 1, 'php', 'running', 'www-data', 2048, 0.25, 2, null], [
            $worker->pid, $worker->parentPid, $worker->name, $worker->state, $worker->user,
            $worker->memoryBytes, $worker->cpuSeconds, $worker->threads, $worker->startedAt,
        ]);
        self::assertSame($started, $master->startedAt);
        self::assertSame('Processes retrieved successfully', $response->message);
    }

    public function testTheLogFilesComeWithTheirFolder(): void
    {
        $files = [new LogFileSummary(name: 'prod.log', sizeBytes: 52341, modifiedAt: new DateTimeImmutable('2026-11-08 14:50:01'))];

        $logs = new class($files) implements LogReaderInterface {
            /**
             * @param list<\App\Application\System\DTO\Shared\Response\Model\LogFileSummary> $files
             */
            public function __construct(private array $files)
            {
            }

            public function directory(): string
            {
                return '/app/var/log';
            }

            public function files(): array
            {
                return $this->files;
            }

            public function file(string $name): ?LogFileSummary
            {
                return null;
            }

            public function entries(string $name): iterable
            {
                return [new LogEntry(datetime: null, channel: null, level: null, message: 'never read')];
            }
        };

        $response = new ListLogFilesUseCase($logs, new IdentityTranslator())->execute();

        self::assertSame('/app/var/log', $response->directory);
        self::assertSame($files, $response->files);
        self::assertSame('Log files retrieved successfully', $response->message);
    }

    private function dependencies(DatabaseStatus $database, ExternalServiceStatus $tmdb): GetDependenciesStatusUseCase
    {
        $databaseInspector = new class($database) implements DatabaseInspectorInterface {
            public function __construct(private DatabaseStatus $status)
            {
            }

            public function inspect(): DatabaseStatus
            {
                return $this->status;
            }
        };
        $probe = new class($tmdb) implements MovieDatabaseProbeInterface {
            public function __construct(private ExternalServiceStatus $status)
            {
            }

            public function probe(): ExternalServiceStatus
            {
                return $this->status;
            }
        };

        return new GetDependenciesStatusUseCase($databaseInspector, $probe, $this->masker(), new IdentityTranslator());
    }

    private function masker(): SecretMaskerInterface
    {
        return new class implements SecretMaskerInterface {
            public function mask(string $text): string
            {
                return str_replace('hunter2', '***', $text);
            }
        };
    }
}
