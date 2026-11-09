<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\System;

use App\Application\System\DTO\Server\Response\Model\DiskInfo;
use App\Infrastructure\System\ProcServerInspector;
use PHPUnit\Framework\TestCase;

/**
 * ProcServerInspector com um /proc, um /sys/fs/cgroup e um /etc/os-release
 * de exemplo (numa pasta temporária): a máquina, o cgroup v2 (cgroupns
 * privado, o padrão do Docker, e o do host), o v1, a falta de cgroup e os
 * discos do mountinfo.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ProcServerInspectorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/proc-server-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/proc/self', 0777, true);
        mkdir($this->root . '/cgroup', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testTheHostComesFromProc(): void
    {
        $this->file('proc/sys/kernel/hostname', "3f2a9c1b7d4e\n");
        $this->file('proc/sys/kernel/osrelease', "6.6.87.2-microsoft-standard-WSL2\n");
        $this->file('proc/cpuinfo', "processor\t: 0\nmodel name\t: CPU\n\nprocessor\t: 1\nmodel name\t: CPU\n\nprocessor\t: 2\n\nprocessor\t: 3\n");
        $this->file('proc/uptime', "86400.52 172000.10\n");
        $this->file('proc/loadavg', "0.52 0.48 0.40 2/345 6789\n");
        $this->file('proc/meminfo', "MemTotal:       16234567 kB\nMemFree:         1000000 kB\nMemAvailable:    9583970 kB\nBuffers:          100 kB\nSwapTotal:       4194304 kB\nSwapFree:        4000000 kB\n");
        $this->file('os-release', "NAME=\"Debian GNU/Linux\"\nPRETTY_NAME=\"Debian GNU/Linux 12 (bookworm)\"\nID=debian\n");

        $host = $this->inspector()->host();

        self::assertSame('3f2a9c1b7d4e', $host->hostname);
        self::assertSame('Debian GNU/Linux 12 (bookworm)', $host->os);
        self::assertSame('6.6.87.2-microsoft-standard-WSL2', $host->kernel);
        self::assertSame(php_uname('m'), $host->architecture);
        self::assertSame(4, $host->cpus);
        self::assertSame(86400, $host->uptimeSeconds);
        self::assertSame([0.52, 0.48, 0.4], $host->loadAverage);
        self::assertNotNull($host->memory);
        self::assertSame(
            [16234567 * 1024, 9583970 * 1024, 4194304 * 1024, 4000000 * 1024],
            [$host->memory->totalBytes, $host->memory->availableBytes, $host->memory->swapTotalBytes, $host->memory->swapFreeBytes],
        );
    }

    public function testOldKernelsWithoutMemAvailable(): void
    {
        $this->file('proc/meminfo', "MemTotal: 1000 kB\nMemFree: 100 kB\nBuffers: 20 kB\nCached: 30 kB\n");

        $memory = $this->inspector()->host()->memory;

        self::assertNotNull($memory);
        self::assertSame([1000 * 1024, 150 * 1024, 0, 0], [$memory->totalBytes, $memory->availableBytes, $memory->swapTotalBytes, $memory->swapFreeBytes]);
    }

    public function testWithoutProcTheHostStillHasANameAndAKernel(): void
    {
        $host = $this->inspector()->host();

        self::assertSame(gethostname(), $host->hostname);
        self::assertSame(php_uname('r'), $host->kernel);
        self::assertSame([null, null, null, null, null], [$host->os, $host->cpus, $host->uptimeSeconds, $host->loadAverage, $host->memory]);
    }

    /**
     * cgroupns privado (o padrão do Docker): o /proc/self/cgroup diz "/" e
     * o cgroup do contêiner é a raiz do /sys/fs/cgroup.
     */
    public function testCgroupV2WithAPrivateNamespace(): void
    {
        $this->file('proc/self/cgroup', "0::/\n");
        $this->file('cgroup/cgroup.controllers', "cpu memory pids\n");
        $this->file('cgroup/memory.max', "1073741824\n");
        $this->file('cgroup/memory.current', "268435456\n");
        $this->file('cgroup/cpu.max', "150000 100000\n");
        $this->file('cgroup/pids.max', "max\n");
        $this->file('cgroup/pids.current', "12\n");

        $container = $this->inspector()->container();

        self::assertNotNull($container);
        self::assertSame(
            [2, 1073741824, 268435456, 1.5, null, 12],
            [$container->cgroupVersion, $container->memoryLimitBytes, $container->memoryUsageBytes, $container->cpuLimit, $container->processesLimit, $container->processes],
        );
    }

    /**
     * cgroupns do host: o caminho do /proc/self/cgroup fica dentro do
     * /sys/fs/cgroup. "max" é sem limite.
     */
    public function testCgroupV2WithTheNamespaceOfTheHost(): void
    {
        $this->file('proc/self/cgroup', "0::/system.slice/docker-abc.scope\n");
        $this->file('cgroup/cgroup.controllers', "cpu memory pids\n");
        $this->file('cgroup/system.slice/docker-abc.scope/cgroup.controllers', "cpu memory pids\n");
        $this->file('cgroup/system.slice/docker-abc.scope/memory.max', "max\n");
        $this->file('cgroup/system.slice/docker-abc.scope/memory.current', "1048576\n");
        $this->file('cgroup/system.slice/docker-abc.scope/cpu.max', "max 100000\n");
        $this->file('cgroup/system.slice/docker-abc.scope/pids.max', "4096\n");
        $this->file('cgroup/system.slice/docker-abc.scope/pids.current', "3\n");

        $container = $this->inspector()->container();

        self::assertNotNull($container);
        self::assertSame(
            [2, null, 1048576, null, 4096, 3],
            [$container->cgroupVersion, $container->memoryLimitBytes, $container->memoryUsageBytes, $container->cpuLimit, $container->processesLimit, $container->processes],
        );
    }

    public function testCgroupV1(): void
    {
        $this->file('proc/self/cgroup', "12:pids:/docker/abc\n11:memory:/docker/abc\n4:cpu,cpuacct:/docker/abc\n1:name=systemd:/docker/abc\n0::/system.slice/containerd.service\n");
        $this->file('cgroup/memory/memory.limit_in_bytes', "9223372036854771712\n");
        $this->file('cgroup/memory/memory.usage_in_bytes', "123456\n");
        $this->file('cgroup/cpu,cpuacct/cpu.cfs_quota_us', "200000\n");
        $this->file('cgroup/cpu,cpuacct/cpu.cfs_period_us', "100000\n");
        $this->file('cgroup/pids/pids.max', "max\n");
        $this->file('cgroup/pids/pids.current', "5\n");

        $container = $this->inspector()->container();

        self::assertNotNull($container);
        self::assertSame(
            [1, null, 123456, 2.0, null, 5],
            [$container->cgroupVersion, $container->memoryLimitBytes, $container->memoryUsageBytes, $container->cpuLimit, $container->processesLimit, $container->processes],
        );
    }

    public function testCgroupV1WithLimits(): void
    {
        $this->file('proc/self/cgroup', "11:memory:/docker/abc\n4:cpu,cpuacct:/docker/abc\n");
        $this->file('cgroup/memory/docker/abc/memory.limit_in_bytes', "536870912\n");
        $this->file('cgroup/memory/docker/abc/memory.usage_in_bytes', "1024\n");
        $this->file('cgroup/cpu/docker/abc/cpu.cfs_quota_us', "-1\n");
        $this->file('cgroup/cpu/docker/abc/cpu.cfs_period_us', "100000\n");

        $container = $this->inspector()->container();

        self::assertNotNull($container);
        self::assertSame(
            [1, 536870912, 1024, null, null, null],
            [$container->cgroupVersion, $container->memoryLimitBytes, $container->memoryUsageBytes, $container->cpuLimit, $container->processesLimit, $container->processes],
        );
    }

    public function testWithoutACgroupToReadThereIsNoContainer(): void
    {
        self::assertNull($this->inspector()->container(), 'no /proc/self/cgroup');

        // a raiz do cgroup v2 do host não tem os arquivos de uso
        $this->file('proc/self/cgroup', "0::/\n");
        $this->file('cgroup/cgroup.controllers', "cpu memory pids\n");
        self::assertNull($this->inspector()->container(), 'the root of the host');
    }

    public function testTheDisksComeFromTheMountInfo(): void
    {
        mkdir($this->root . '/data');
        mkdir($this->root . '/with space');
        $this->file('hosts', "127.0.0.1 localhost\n");
        $data = $this->root . '/data';
        $withSpace = str_replace(' ', '\\040', $this->root . '/with space');
        $this->file('proc/self/mountinfo', implode("\n", [
            "21 1 0:20 / / rw,relatime - overlay overlay rw,lowerdir=/l",
            "22 21 0:21 / /proc rw,nosuid - proc proc rw",
            "23 21 0:22 / /dev rw,nosuid - tmpfs tmpfs rw",
            "24 21 0:23 / /sys/fs/cgroup ro - cgroup2 cgroup rw",
            "25 21 8:1 /etc/hosts {$this->root}/hosts rw - ext4 /dev/sda1 rw",
            "26 21 0:30 / {$data} rw - 9p drvfs rw,aname=drvfs",
            "27 21 0:31 / {$withSpace} rw - ext4 /dev/sdb rw",
            "28 26 0:32 / {$data} rw shared:1 - fuse.rclone remote: rw",
            "29 21 0:33 / /run/secrets ro - tmpfs tmpfs ro",
            "a broken line",
        ]) . "\n");

        $disks = $this->inspector()->disks();
        $byMountPoint = [];
        foreach ($disks as $disk) {
            $byMountPoint[$disk->mountPoint] = $disk->filesystem;
        }

        self::assertSame(['/' => 'overlay', $data => 'fuse.rclone', $this->root . '/with space' => 'ext4'], $byMountPoint);
        foreach ($disks as $disk) {
            self::assertInstanceOf(DiskInfo::class, $disk);
            self::assertGreaterThan(0, $disk->totalBytes);
            self::assertLessThanOrEqual($disk->totalBytes, $disk->freeBytes);
        }
    }

    public function testWithoutMountInfoThereAreNoDisks(): void
    {
        self::assertSame([], $this->inspector()->disks());
    }

    private function inspector(): ProcServerInspector
    {
        return new ProcServerInspector($this->root . '/proc', $this->root . '/cgroup', $this->root . '/os-release');
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
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) ?: [] as $name) {
                if ($name !== '.' && $name !== '..') {
                    $this->remove($path . '/' . $name);
                }
            }
            rmdir($path);
        } elseif (file_exists($path) || is_link($path)) {
            unlink($path);
        }
    }
}
