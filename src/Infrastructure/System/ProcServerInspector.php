<?php

declare(strict_types = 1);

namespace App\Infrastructure\System;

use App\Application\System\DTO\Server\Response\Model\ContainerInfo;
use App\Application\System\DTO\Server\Response\Model\DiskInfo;
use App\Application\System\DTO\Server\Response\Model\HostInfo;
use App\Application\System\DTO\Server\Response\Model\MemoryInfo;
use App\Application\System\Port\ServerInspectorInterface;

/**
 * Implementação da ServerInspectorInterface para Linux: lê o /proc e o
 * /sys/fs/cgroup (as raízes vêm no construtor; os testes apontam para
 * pastas com arquivos de exemplo).
 *
 * - host: /proc/sys/kernel/{hostname,osrelease}, /proc/cpuinfo,
 *   /proc/uptime, /proc/loadavg, /proc/meminfo e o /etc/os-release;
 * - container: o cgroup do processo (/proc/self/cgroup). No cgroup v2 (o
 *   padrão do Docker atual) memory.max/current, cpu.max e pids.max/current;
 *   no v1, os arquivos equivalentes de cada controlador. No Docker a pasta
 *   do cgroup é a raiz do /sys/fs/cgroup (cgroupns privado) ou o caminho
 *   do /proc/self/cgroup (cgroupns do host) — as duas são tentadas;
 * - disks: /proc/self/mountinfo, sem os sistemas de arquivos virtuais,
 *   sem o que está em /proc, /sys, /dev e /run, e sem as montagens de um
 *   arquivo só (o Docker monta /etc/hosts, /etc/hostname...).
 *
 * Arquivo que não existe ou não pode ser lido vira null no campo, nunca
 * exceção.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class ProcServerInspector implements ServerInspectorInterface {
    private const array VIRTUAL_FILESYSTEMS = [
        'autofs', 'binfmt_misc', 'bpf', 'cgroup', 'cgroup2', 'configfs', 'debugfs', 'devpts', 'devtmpfs',
        'efivarfs', 'fusectl', 'hugetlbfs', 'mqueue', 'nsfs', 'proc', 'pstore', 'rpc_pipefs', 'securityfs',
        'selinuxfs', 'sysfs', 'tracefs',
    ];

    private const array VIRTUAL_PATHS = ['/proc', '/sys', '/dev', '/run'];

    private const int MAX_DISKS = 50;

    /**
     * A partir daqui um limite do cgroup v1 é "sem limite" (o kernel usa
     * 9223372036854771712, quase o PHP_INT_MAX).
     */
    private const int UNLIMITED_V1 = 1 << 62;

    /**
     * @param string $procRoot      o /proc
     * @param string $cgroupRoot    o /sys/fs/cgroup
     * @param string $osReleaseFile o /etc/os-release
     */
    public function __construct(
        private string $procRoot = '/proc',
        private string $cgroupRoot = '/sys/fs/cgroup',
        private string $osReleaseFile = '/etc/os-release',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function host(): HostInfo {
        $hostname = $this->firstLine($this->procRoot . '/sys/kernel/hostname') ?? gethostname();

        return new HostInfo(
            hostname: mb_scrub($hostname === false ? php_uname('n') : $hostname, 'UTF-8'),
            os: $this->osName(),
            kernel: mb_scrub($this->firstLine($this->procRoot . '/sys/kernel/osrelease') ?? php_uname('r'), 'UTF-8'),
            architecture: php_uname('m'),
            cpus: $this->cpus(),
            uptimeSeconds: $this->uptime(),
            loadAverage: $this->loadAverage(),
            memory: $this->memory(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function container(): ?ContainerInfo {
        $cgroups = $this->read($this->procRoot . '/self/cgroup');

        if ($cgroups === null) {
            return null;
        }

        // controlador => caminho do cgroup ('' = a hierarquia única do v2)
        $paths = [];

        foreach (explode("\n", trim($cgroups)) as $line) {
            $parts = explode(':', $line, 3);

            if (count($parts) !== 3) {
                continue;
            }

            [$id, $controllers, $path] = $parts;

            if ($id === '0' && $controllers === '') {
                $paths[''] = $path;

                continue;
            }

            foreach (explode(',', $controllers) as $controller) {
                $paths[$controller] = $path;
            }
        }

        $unified = isset($paths['']) ? $this->cgroupDirectory('', $paths[''], 'cgroup.controllers') : null;

        if ($unified !== null) {
            return $this->cgroupV2($unified);
        }

        return $this->cgroupV1($paths);
    }

    /**
     * {@inheritDoc}
     */
    public function disks(): array {
        $mountInfo = $this->read($this->procRoot . '/self/mountinfo');

        if ($mountInfo === null) {
            return [];
        }

        // ponto de montagem => tipo (a última montagem num ponto é a que vale)
        $mounts = [];

        foreach (explode("\n", trim($mountInfo)) as $line) {
            $separator = strpos($line, ' - ');

            if ($separator === false) {
                continue;
            }

            $fields = explode(' ', substr($line, 0, $separator));
            $type = explode(' ', substr($line, $separator + 3))[0];

            if (count($fields) < 5 || in_array($type, self::VIRTUAL_FILESYSTEMS, true)) {
                continue;
            }

            $mountPoint = self::unescape($fields[4]);

            if (!self::isVirtualPath($mountPoint)) {
                $mounts[$mountPoint] = $type;
            }
        }

        ksort($mounts, SORT_STRING);
        $disks = [];

        foreach ($mounts as $mountPoint => $type) {
            $mountPoint = (string) $mountPoint;

            if (!is_dir($mountPoint)) {
                continue;
            }

            $total = @disk_total_space($mountPoint);
            $free = @disk_free_space($mountPoint);

            if (!is_float($total) || !is_float($free) || $total <= 0) {
                continue;
            }

            $disks[] = new DiskInfo(
                mountPoint: mb_scrub($mountPoint, 'UTF-8'),
                filesystem: mb_scrub($type, 'UTF-8'),
                totalBytes: (int) $total,
                freeBytes: (int) $free,
            );

            if (count($disks) === self::MAX_DISKS) {
                break;
            }
        }

        return $disks;
    }

    /**
     * O PRETTY_NAME do os-release ("Debian GNU/Linux 12 (bookworm)").
     *
     * @return string|null
     */
    private function osName(): ?string {
        $release = $this->read($this->osReleaseFile);

        if ($release === null || preg_match('/^PRETTY_NAME=(.*)$/m', $release, $match) !== 1) {
            return null;
        }

        $name = trim(trim($match[1]), '"\'');

        return $name === '' ? null : mb_scrub($name, 'UTF-8');
    }

    /**
     * @return int|null
     */
    private function cpus(): ?int {
        $cpuInfo = $this->read($this->procRoot . '/cpuinfo');
        $count = $cpuInfo === null ? 0 : (int) preg_match_all('/^processor\s*:/m', $cpuInfo);

        return $count > 0 ? $count : null;
    }

    /**
     * @return int|null
     */
    private function uptime(): ?int {
        $uptime = $this->firstLine($this->procRoot . '/uptime');
        $seconds = $uptime === null ? '' : explode(' ', $uptime)[0];

        return is_numeric($seconds) ? (int) $seconds : null;
    }

    /**
     * @return list<float>|null
     */
    private function loadAverage(): ?array {
        $load = $this->firstLine($this->procRoot . '/loadavg');
        $values = $load === null ? [] : array_slice(explode(' ', $load), 0, 3);

        if (count($values) !== 3 || !is_numeric($values[0]) || !is_numeric($values[1]) || !is_numeric($values[2])) {
            return null;
        }

        return [round((float) $values[0], 2), round((float) $values[1], 2), round((float) $values[2], 2)];
    }

    /**
     * @return \App\Application\System\DTO\Server\Response\Model\MemoryInfo|null
     */
    private function memory(): ?MemoryInfo {
        $memInfo = $this->read($this->procRoot . '/meminfo');

        if ($memInfo === null || preg_match_all('/^(\w+):\s+(\d+)/m', $memInfo, $matches, PREG_SET_ORDER) === 0) {
            return null;
        }

        $kib = [];
        foreach ($matches as $match) {
            $kib[$match[1]] = (int) $match[2];
        }

        if (!isset($kib['MemTotal'])) {
            return null;
        }

        // kernels antigos não têm MemAvailable
        $available = $kib['MemAvailable'] ?? (($kib['MemFree'] ?? 0) + ($kib['Buffers'] ?? 0) + ($kib['Cached'] ?? 0));

        return new MemoryInfo(
            totalBytes: $kib['MemTotal'] * 1024,
            availableBytes: $available * 1024,
            swapTotalBytes: ($kib['SwapTotal'] ?? 0) * 1024,
            swapFreeBytes: ($kib['SwapFree'] ?? 0) * 1024,
        );
    }

    /**
     * @param string $directory a pasta do cgroup (v2)
     *
     * @return \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null
     */
    private function cgroupV2(string $directory): ?ContainerInfo {
        $memoryUsage = $this->integer($directory . '/memory.current');
        $processes = $this->integer($directory . '/pids.current');
        $cpuMax = $this->firstLine($directory . '/cpu.max');

        // a raiz do cgroup do host não tem estes arquivos: não há o que mostrar
        if ($memoryUsage === null && $processes === null && $cpuMax === null) {
            return null;
        }

        $cpuLimit = null;
        $cpu = $cpuMax === null ? [] : explode(' ', $cpuMax);

        if (count($cpu) === 2 && ctype_digit($cpu[0]) && ctype_digit($cpu[1]) && (int) $cpu[1] > 0) {
            $cpuLimit = round((int) $cpu[0] / (int) $cpu[1], 2);
        }

        return new ContainerInfo(
            cgroupVersion: 2,
            memoryLimitBytes: $this->integer($directory . '/memory.max'),
            memoryUsageBytes: $memoryUsage,
            cpuLimit: $cpuLimit,
            processesLimit: $this->integer($directory . '/pids.max'),
            processes: $processes,
        );
    }

    /**
     * @param array<string, string> $paths controlador => caminho do cgroup
     *
     * @return \App\Application\System\DTO\Server\Response\Model\ContainerInfo|null
     */
    private function cgroupV1(array $paths): ?ContainerInfo {
        $memory = isset($paths['memory']) ? $this->cgroupDirectory('/memory', $paths['memory'], 'memory.usage_in_bytes') : null;
        $cpu = isset($paths['cpu'])
            ? $this->cgroupDirectory('/cpu', $paths['cpu'], 'cpu.cfs_quota_us') ?? $this->cgroupDirectory('/cpu,cpuacct', $paths['cpu'], 'cpu.cfs_quota_us')
            : null;
        $pids = isset($paths['pids']) ? $this->cgroupDirectory('/pids', $paths['pids'], 'pids.current') : null;

        if ($memory === null && $cpu === null && $pids === null) {
            return null;
        }

        $memoryLimit = $memory === null ? null : $this->integer($memory . '/memory.limit_in_bytes');
        $quota = $cpu === null ? null : $this->integer($cpu . '/cpu.cfs_quota_us');
        $period = $cpu === null ? null : $this->integer($cpu . '/cpu.cfs_period_us');

        return new ContainerInfo(
            cgroupVersion: 1,
            memoryLimitBytes: $memoryLimit !== null && $memoryLimit < self::UNLIMITED_V1 ? $memoryLimit : null,
            memoryUsageBytes: $memory === null ? null : $this->integer($memory . '/memory.usage_in_bytes'),
            cpuLimit: $quota !== null && $quota > 0 && $period !== null && $period > 0 ? round($quota / $period, 2) : null,
            processesLimit: $pids === null ? null : $this->integer($pids . '/pids.max'),
            processes: $pids === null ? null : $this->integer($pids . '/pids.current'),
        );
    }

    /**
     * A pasta do cgroup: o caminho do /proc/self/cgroup dentro da montagem
     * (cgroupns do host) ou a própria montagem (cgroupns privado, o padrão
     * do Docker) — a primeira que tem $marker.
     *
     * @param string $mount  '' (v2) ou '/<controlador>' (v1)
     * @param string $path   o caminho do /proc/self/cgroup
     * @param string $marker um arquivo que só a pasta certa tem
     *
     * @return string|null
     */
    private function cgroupDirectory(string $mount, string $path, string $marker): ?string {
        $base = rtrim($this->cgroupRoot, '/') . $mount;

        foreach ([rtrim($base . $path, '/'), $base] as $directory) {
            if (is_file($directory . '/' . $marker)) {
                return $directory;
            }
        }

        return null;
    }

    /**
     * O número de um arquivo do cgroup; "max" (sem limite), vazio ou
     * ilegível: null.
     *
     * @param string $path
     *
     * @return int|null
     */
    private function integer(string $path): ?int {
        $value = $this->firstLine($path);

        return $value !== null && preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : null;
    }

    /**
     * @param string $path
     *
     * @return string|null a primeira linha, sem espaços nas pontas; null se vazio ou ilegível
     */
    private function firstLine(string $path): ?string {
        $content = $this->read($path);
        $line = $content === null ? '' : trim(explode("\n", $content, 2)[0]);

        return $line === '' ? null : $line;
    }

    /**
     * O conteúdo do arquivo; null quando ele não existe ou não pode ser
     * lido. A checagem vem antes da leitura porque, com o debug ligado, o
     * ErrorHandler do Symfony registra no log até os avisos silenciados
     * com @.
     *
     * @param string $path
     *
     * @return string|null
     */
    private function read(string $path): ?string {
        $content = is_file($path) && is_readable($path) ? @file_get_contents($path) : false;

        return is_string($content) ? $content : null;
    }

    /**
     * O mountinfo escapa espaço, tab, quebra de linha e barra invertida em
     * octal (\040, \011, \012, \134).
     *
     * @param string $path
     *
     * @return string
     */
    private static function unescape(string $path): string {
        return (string) preg_replace_callback('/\\\\([0-7]{3})/', static fn(array $match): string => chr((int) octdec($match[1])), $path);
    }

    /**
     * @param string $mountPoint
     *
     * @return bool
     */
    private static function isVirtualPath(string $mountPoint): bool {
        foreach (self::VIRTUAL_PATHS as $virtual) {
            if ($mountPoint === $virtual || str_starts_with($mountPoint, $virtual . '/')) {
                return true;
            }
        }

        return false;
    }
}
