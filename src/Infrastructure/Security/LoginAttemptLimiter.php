<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

/**
 * Conta os logins que falharam e diz quanto falta para o próximo poder
 * ser tentado.
 *
 * São duas contagens, cada uma numa janela fixa de WINDOW_SECONDS que
 * começa na primeira falha:
 * - IP + email: quem erra a senha de uma conta. Com
 *   MAX_FAILURES_PER_ACCOUNT falhas, aquele IP não tenta mais aquela conta
 *   até a janela acabar;
 * - só o IP: quem testa muitas contas com senhas vazadas de outros sites
 *   (credential stuffing). Com MAX_FAILURES_PER_IP falhas, o IP não tenta
 *   nenhuma conta até a janela acabar.
 *
 * Um login certo zera só a contagem da conta. Zerar também a do IP
 * deixaria quem tem uma conta válida "limpar" o IP entre uma rodada de
 * tentativas e outra.
 *
 * O cache guarda só hashes nas chaves (nem email nem IP em claro) e, no
 * valor, o número de falhas e quando a janela acaba.
 *
 * NOTA (nov/2026): criado para limitar as tentativas de login
 * (LoginThrottlingListener, firewall "login"). O login_throttling do
 * próprio Symfony faria o mesmo, mas exige o pacote symfony/rate-limiter,
 * que não está no composer.json. As regras são as do limitador padrão do
 * Symfony (IP + usuário, e só IP com 5x o limite), com janela de 15
 * minutos em vez de 1.
 */
final readonly class LoginAttemptLimiter {
    /**
     * Falhas de um IP numa mesma conta, por janela.
     */
    public const int MAX_FAILURES_PER_ACCOUNT = 5;

    /**
     * Falhas de um IP em qualquer conta, por janela.
     */
    public const int MAX_FAILURES_PER_IP = 25;

    /**
     * Duração da janela, a partir da primeira falha (15 minutos).
     */
    public const int WINDOW_SECONDS = 900;

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache pool cache.login_throttling (config/packages/cache.yaml)
     * @param \Psr\Clock\ClockInterface         $clock
     */
    public function __construct(
        private CacheItemPoolInterface $cache,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Segundos até o IP poder tentar de novo (nessa conta, quando o email
     * é conhecido); 0 quando ele pode tentar agora.
     *
     * @param string      $ip
     * @param string|null $email null quando a tentativa não informou um usuário
     *
     * @return int
     */
    public function secondsUntilAllowed(string $ip, ?string $email): int {
        $now = $this->now();
        $wait = 0;

        foreach ($this->counters($ip, $email) as $key => $maxFailures) {
            $window = $this->openWindow($key, $now);

            if ($window !== null && $window['failures'] >= $maxFailures) {
                $wait = max($wait, $window['reset_at'] - $now);
            }
        }

        return $wait;
    }

    /**
     * Conta uma falha nas duas contagens (na do IP, quando não há email).
     *
     * @param string      $ip
     * @param string|null $email
     *
     * @return void
     */
    public function recordFailure(string $ip, ?string $email): void {
        $now = $this->now();

        foreach (array_keys($this->counters($ip, $email)) as $key) {
            $window = $this->openWindow($key, $now) ?? [
                'failures' => 0,
                'reset_at' => $now + self::WINDOW_SECONDS,
            ];
            $window['failures']++;

            $item = $this->cache->getItem($key);
            $item->set($window);
            $item->expiresAfter(max(1, $window['reset_at'] - $now));
            $this->cache->save($item);
        }
    }

    /**
     * Login certo: zera a contagem da conta (a do IP continua).
     *
     * @param string $ip
     * @param string $email
     *
     * @return void
     */
    public function recordSuccess(string $ip, string $email): void {
        $this->cache->deleteItem($this->accountKey($ip, $email));
    }

    /**
     * As contagens que valem para a tentativa: chave => limite de falhas.
     *
     * @param string      $ip
     * @param string|null $email
     *
     * @return array<string, int>
     */
    private function counters(string $ip, ?string $email): array {
        $counters = ['ip_' . hash('sha256', $ip) => self::MAX_FAILURES_PER_IP];

        if ($email !== null) {
            $counters[$this->accountKey($ip, $email)] = self::MAX_FAILURES_PER_ACCOUNT;
        }

        return $counters;
    }

    /**
     * @param string $ip
     * @param string $email
     *
     * @return string
     */
    private function accountKey(string $ip, string $email): string {
        return 'account_' . hash('sha256', $ip . '|' . mb_strtolower(trim($email)));
    }

    /**
     * A janela guardada na chave, se ela ainda não acabou.
     *
     * @param string $key
     * @param int    $now
     *
     * @return array{failures: int, reset_at: int}|null
     */
    private function openWindow(string $key, int $now): ?array {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        $window = $item->get();

        if (
            !is_array($window)
            || !is_int($window['failures'] ?? null)
            || !is_int($window['reset_at'] ?? null)
            || $window['reset_at'] <= $now
        ) {
            return null;
        }

        return ['failures' => $window['failures'], 'reset_at' => $window['reset_at']];
    }

    /**
     * @return int
     */
    private function now(): int {
        return $this->clock->now()->getTimestamp();
    }
}
