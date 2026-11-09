<?php

declare(strict_types = 1);

namespace App\Infrastructure\System;

use App\Application\System\DTO\Processes\Response\Model\ProcessInfo;
use App\Application\System\Port\ProcessInspectorInterface;
use DateTimeImmutable;

/**
 * Implementação da ProcessInspectorInterface para Linux: um ProcessInfo
 * para cada /proc/<pid> (a raiz vem no construtor; os testes apontam para
 * uma pasta com arquivos de exemplo).
 *
 * - /proc/<pid>/stat: nome, estado, PID do pai, tempo de CPU, threads e o
 *   início (em ticks desde o boot; o boot vem do btime do /proc/stat);
 * - /proc/<pid>/status: o uid (o nome vem do /etc/passwd) e a memória
 *   residente (VmRSS);
 * - /proc/<pid>/cmdline: a linha de comando (os argumentos vêm separados
 *   por \0).
 * O /proc/<pid>/environ — as variáveis de ambiente, onde ficam os
 * segredos — nunca é lido.
 *
 * Um processo que termina no meio da leitura fica de fora. O tick do
 * kernel para o usuário (USER_HZ) é 100 em todo Linux de x86 e ARM.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class ProcProcessInspector implements ProcessInspectorInterface {
    private const int TICKS_PER_SECOND = 100;

    private const int MAX_PROCESSES = 1_000;

    /**
     * A letra do estado no /proc/<pid>/stat => o estado no JSON.
     */
    private const array STATES = [
        'R' => 'running',
        'S' => 'sleeping',
        'D' => 'waiting',
        'T' => 'stopped',
        't' => 'stopped',
        'Z' => 'zombie',
        'I' => 'idle',
        'X' => 'dead',
        'x' => 'dead',
    ];

    /**
     * @param string $procRoot   o /proc
     * @param string $passwdFile o /etc/passwd (uid => nome do usuário)
     */
    public function __construct(
        private string $procRoot = '/proc',
        private string $passwdFile = '/etc/passwd',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processes(): array {
        $entries = is_dir($this->procRoot) ? @scandir($this->procRoot) : false;

        if ($entries === false) {
            return [];
        }

        $pids = array_map('intval', array_filter($entries, static fn(string $entry): bool => ctype_digit($entry)));
        sort($pids);

        $bootTime = $this->bootTime();
        $users = $this->users();
        $processes = [];

        foreach (array_slice($pids, 0, self::MAX_PROCESSES) as $pid) {
            $process = $this->process($pid, $bootTime, $users);

            if ($process !== null) {
                $processes[] = $process;
            }
        }

        return $processes;
    }

    /**
     * @param int                $pid
     * @param int|null           $bootTime quando a máquina ligou (Unix time)
     * @param array<int, string> $users    uid => nome
     *
     * @return \App\Application\System\DTO\Processes\Response\Model\ProcessInfo|null
     */
    private function process(int $pid, ?int $bootTime, array $users): ?ProcessInfo {
        $stat = $this->read("{$this->procRoot}/{$pid}/stat");
        // o nome fica entre parênteses e pode ter espaços e parênteses: vale o último ")"
        $open = $stat === null ? false : strpos($stat, '(');
        $close = $stat === null ? false : strrpos($stat, ')');

        if ($stat === null || $open === false || $close === false || $close < $open) {
            return null;
        }

        // a partir do campo 3 (estado): [0] estado, [1] PID do pai, [11] utime,
        // [12] stime, [17] threads, [19] início (ticks desde o boot)
        $fields = explode(' ', trim(substr($stat, $close + 1)));

        if (count($fields) < 20) {
            return null;
        }

        $name = mb_scrub(substr($stat, $open + 1, $close - $open - 1), 'UTF-8');
        $status = $this->status($pid);
        // "Uid:\t1000\t1000\t1000\t1000": o primeiro é o uid real
        $uid = isset($status['Uid']) && preg_match('/^\d+/', $status['Uid'], $match) === 1 ? (int) $match[0] : null;
        $command = trim(str_replace("\0", ' ', $this->read("{$this->procRoot}/{$pid}/cmdline") ?? ''));
        $startTicks = (int) $fields[19];

        return new ProcessInfo(
            pid: $pid,
            parentPid: (int) $fields[1],
            name: $name,
            command: $command === '' ? "[{$name}]" : mb_scrub($command, 'UTF-8'),
            state: self::STATES[$fields[0]] ?? 'unknown',
            user: $uid === null ? '?' : ($users[$uid] ?? (string) $uid),
            memoryBytes: isset($status['VmRSS']) ? (int) $status['VmRSS'] * 1024 : 0,
            cpuSeconds: round(((int) $fields[11] + (int) $fields[12]) / self::TICKS_PER_SECOND, 2),
            threads: (int) $fields[17],
            startedAt: $bootTime === null ? null : new DateTimeImmutable('@' . ($bootTime + intdiv($startTicks, self::TICKS_PER_SECOND))),
        );
    }

    /**
     * As linhas "Chave:\tvalor" do /proc/<pid>/status.
     *
     * @param int $pid
     *
     * @return array<string, string>
     */
    private function status(int $pid): array {
        $status = $this->read("{$this->procRoot}/{$pid}/status") ?? '';
        $fields = [];

        foreach (explode("\n", $status) as $line) {
            $separator = strpos($line, ':');

            if ($separator !== false) {
                $fields[substr($line, 0, $separator)] = trim(substr($line, $separator + 1));
            }
        }

        return $fields;
    }

    /**
     * O btime do /proc/stat: quando a máquina ligou.
     *
     * @return int|null
     */
    private function bootTime(): ?int {
        $stat = $this->read($this->procRoot . '/stat');

        return $stat !== null && preg_match('/^btime\s+(\d+)/m', $stat, $match) === 1 ? (int) $match[1] : null;
    }

    /**
     * @return array<int, string> uid => nome do usuário
     */
    private function users(): array {
        $users = [];

        foreach (explode("\n", $this->read($this->passwdFile) ?? '') as $line) {
            $fields = explode(':', $line);

            if (count($fields) >= 3 && $fields[0] !== '' && ctype_digit($fields[2])) {
                $users[(int) $fields[2]] ??= mb_scrub($fields[0], 'UTF-8');
            }
        }

        return $users;
    }

    /**
     * O conteúdo do arquivo; null quando ele não existe (o processo
     * terminou) ou não pode ser lido. A checagem vem antes da leitura
     * porque, com o debug ligado, o ErrorHandler do Symfony registra no log
     * até os avisos silenciados com @.
     *
     * @param string $path
     *
     * @return string|null
     */
    private function read(string $path): ?string {
        $content = is_file($path) && is_readable($path) ? @file_get_contents($path) : false;

        return is_string($content) ? $content : null;
    }
}
