<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\System;

use App\Application\System\DTO\Processes\Response\Model\ProcessInfo;
use App\Infrastructure\System\ProcProcessInspector;
use PHPUnit\Framework\TestCase;

/**
 * ProcProcessInspector com um /proc e um /etc/passwd de exemplo (numa
 * pasta temporária): o que sai do stat, do status e do cmdline de cada
 * processo, o nome com espaços e parênteses, o zumbi, o processo que
 * terminou no meio da leitura e o usuário que não está no passwd.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ProcProcessInspectorTest extends TestCase
{
    private const int BOOT_TIME = 1762590000;

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/proc-processes-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/proc', 0777, true);
        $this->file('proc/stat', "cpu  100 0 100 1000 0 0 0 0 0 0\nbtime " . self::BOOT_TIME . "\nprocesses 1234\n");
        $this->file('passwd', "root:x:0:0:root:/root:/bin/bash\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin\n");
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testEachProcessComesFromItsFolder(): void
    {
        $this->process(1, '1 (php-fpm) S 0 1 1 0 -1 4194560 100 0 0 0 1250 250 0 0 20 0 1 0 500 123456789 7500', "Name:\tphp-fpm\nUid:\t0\t0\t0\t0\nVmRSS:\t   30720 kB\n", "php-fpm: master process (/usr/local/etc/php-fpm.conf)\0");
        $this->process(42, '42 (my (odd) name) R 1 1 1 0 -1 4194560 10 0 0 0 10 5 0 0 20 0 3 0 12345 98765 300', "Name:\tmy (odd) name\nUid:\t33\t33\t33\t33\nVmRSS:\t    1024 kB\n", "php\0worker.php\0--password=hunter2\0");
        $this->process(77, '77 (defunct) Z 1 1 1 0 -1 4227084 0 0 0 0 0 0 0 0 20 0 1 0 999 0 0', "Name:\tdefunct\nUid:\t1000\t1000\t1000\t1000\n", '');

        $processes = $this->inspector()->processes();

        self::assertSame([1, 42, 77], array_map(static fn(ProcessInfo $process): int => $process->pid, $processes));
        [$master, $worker, $zombie] = $processes;

        self::assertSame(
            [0, 'php-fpm', 'php-fpm: master process (/usr/local/etc/php-fpm.conf)', 'sleeping', 'root', 30720 * 1024, 15.0, 1],
            [$master->parentPid, $master->name, $master->command, $master->state, $master->user, $master->memoryBytes, $master->cpuSeconds, $master->threads],
        );
        self::assertSame(self::BOOT_TIME + 5, $master->startedAt?->getTimestamp());

        // a linha de comando sai como está: os segredos são trocados no UseCase
        self::assertSame(
            [1, 'my (odd) name', 'php worker.php --password=hunter2', 'running', 'www-data', 1024 * 1024, 0.15, 3],
            [$worker->parentPid, $worker->name, $worker->command, $worker->state, $worker->user, $worker->memoryBytes, $worker->cpuSeconds, $worker->threads],
        );
        self::assertSame(self::BOOT_TIME + 123, $worker->startedAt?->getTimestamp());

        self::assertSame(['[defunct]', 'zombie', '1000', 0], [$zombie->command, $zombie->state, $zombie->user, $zombie->memoryBytes]);
    }

    public function testAProcessThatEndedOrIsNotAProcessIsLeftOut(): void
    {
        $this->process(1, '1 (init) S 0 1 1 0 -1 0 0 0 0 0 0 0 0 0 20 0 1 0 1 0 0', "Uid:\t0\t0\t0\t0\n", "init\0");
        mkdir($this->root . '/proc/88');
        mkdir($this->root . '/proc/self');
        $this->file('proc/90/stat', 'garbage');
        $this->file('proc/91/stat', '91 (short) S 0');

        self::assertSame([1], array_map(static fn(ProcessInfo $process): int => $process->pid, $this->inspector()->processes()));
    }

    public function testWithoutTheBootTimeTheStartIsUnknown(): void
    {
        $this->file('proc/stat', "cpu 1 2 3\n");
        $this->process(5, '5 (sh) S 1 1 1 0 -1 0 0 0 0 0 0 0 0 0 20 0 1 0 100 0 0', '', "sh\0");

        $process = $this->inspector()->processes()[0];

        self::assertNull($process->startedAt);
        self::assertSame(['?', 0], [$process->user, $process->memoryBytes]);
    }

    public function testWithoutProcThereAreNoProcesses(): void
    {
        self::assertSame([], new ProcProcessInspector($this->root . '/missing', $this->root . '/passwd')->processes());
    }

    private function inspector(): ProcProcessInspector
    {
        return new ProcProcessInspector($this->root . '/proc', $this->root . '/passwd');
    }

    private function process(int $pid, string $stat, string $status, string $cmdline): void
    {
        $this->file("proc/{$pid}/stat", $stat . "\n");
        $this->file("proc/{$pid}/status", $status);
        $this->file("proc/{$pid}/cmdline", $cmdline);
    }

    private function file(string $path, string $content): void
    {
        $full = $this->root . '/' . $path;

        if (!is_dir(dirname($full))) {
            mkdir(dirname($full), 0777, true);
        }

        file_put_contents($full, $content);
    }

    private function remove(string $path): void
    {
        if (is_dir($path)) {
            foreach (scandir($path) ?: [] as $name) {
                if ($name !== '.' && $name !== '..') {
                    $this->remove($path . '/' . $name);
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }
}
