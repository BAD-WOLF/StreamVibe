<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\System;

use App\Infrastructure\System\PhpRuntimeInspector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * PhpRuntimeInspector no PHP que roda os testes: os limites do php.ini em
 * bytes e segundos (null = sem limite), as extensões em ordem, o OPcache
 * coerente com a extensão e o Symfony do container.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class PhpRuntimeInspectorTest extends TestCase
{
    private string|false $memoryLimit;

    private string|false $maxExecutionTime;

    protected function setUp(): void
    {
        $this->memoryLimit = ini_get('memory_limit');
        $this->maxExecutionTime = ini_get('max_execution_time');
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', (string) $this->memoryLimit);
        ini_set('max_execution_time', (string) $this->maxExecutionTime);
    }

    public function testThePhpOfTheProcess(): void
    {
        $php = $this->inspector()->php();

        self::assertSame(PHP_VERSION, $php->version);
        self::assertSame(PHP_SAPI, $php->sapi);
        self::assertSame(date_default_timezone_get(), $php->timezone);
        self::assertContains('Core', $php->extensions);
        $sorted = $php->extensions;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);
        self::assertSame($sorted, $php->extensions);
        self::assertSame(php_ini_loaded_file() === false ? null : php_ini_loaded_file(), $php->iniFile);
    }

    public function testTheLimitsComeInBytesAndSeconds(): void
    {
        ini_set('memory_limit', '1G');
        ini_set('max_execution_time', '30');

        $php = $this->inspector()->php();

        self::assertSame(1024 ** 3, $php->memoryLimitBytes);
        self::assertSame(30, $php->maxExecutionTimeSeconds);
        self::assertSame(ini_parse_quantity((string) ini_get('upload_max_filesize')), $php->uploadMaxFilesizeBytes);
    }

    public function testNoLimitIsNull(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '0');

        $php = $this->inspector()->php();

        self::assertNull($php->memoryLimitBytes);
        self::assertNull($php->maxExecutionTimeSeconds);
    }

    public function testOpcacheFollowsTheExtension(): void
    {
        $opcache = $this->inspector()->opcache();

        if (!function_exists('opcache_get_status')) {
            self::assertNull($opcache);

            return;
        }

        self::assertNotNull($opcache);
        $status = @opcache_get_status(false);
        $enabled = is_array($status) && ($status['opcache_enabled'] ?? false) === true;
        self::assertSame($enabled, $opcache->enabled);

        if (!$enabled) {
            self::assertSame(
                [false, null, null, null, null, null, null],
                [$opcache->jit, $opcache->memoryUsedBytes, $opcache->memoryFreeBytes, $opcache->memoryWastedBytes, $opcache->cachedScripts, $opcache->hits, $opcache->misses],
            );
        }
    }

    public function testTheSymfonyOfTheContainer(): void
    {
        $symfony = $this->inspector()->symfony();

        self::assertSame(Kernel::VERSION, $symfony->version);
        self::assertSame(['test', true], [$symfony->environment, $symfony->debug]);
        self::assertSame(['ApiPlatformBundle', 'DoctrineBundle', 'FrameworkBundle'], $symfony->bundles);
        [$month, $year] = explode('/', Kernel::END_OF_MAINTENANCE);
        self::assertSame(sprintf('%s-%02d', $year, (int) $month), $symfony->endOfMaintenance);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $symfony->endOfLife);
        self::assertGreaterThanOrEqual($symfony->endOfMaintenance, $symfony->endOfLife);
    }

    private function inspector(): PhpRuntimeInspector
    {
        return new PhpRuntimeInspector('test', true, [
            'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
            'DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle',
            'ApiPlatformBundle' => 'ApiPlatform\\Symfony\\Bundle\\ApiPlatformBundle',
        ]);
    }
}
