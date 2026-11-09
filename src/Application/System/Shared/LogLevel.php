<?php

declare(strict_types = 1);

namespace App\Application\System\Shared;

/**
 * Os níveis de log do PSR-3 (os mesmos do Monolog), do menos ao mais
 * grave. O painel do sistema filtra as linhas dos logs por um nível
 * mínimo (?level=warning traz warning, error, critical, alert e
 * emergency).
 *
 * Nos arquivos o nível vem em maiúsculas ("app.ERROR: ..."); aqui, e no
 * JSON da API, em minúsculas, como no PSR-3.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
enum LogLevel: string {
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Alert = 'alert';
    case Emergency = 'emergency';

    /**
     * A gravidade (0 = debug, 7 = emergency).
     *
     * @return int
     */
    public function severity(): int {
        return match ($this) {
            self::Debug => 0,
            self::Info => 1,
            self::Notice => 2,
            self::Warning => 3,
            self::Error => 4,
            self::Critical => 5,
            self::Alert => 6,
            self::Emergency => 7,
        };
    }

    /**
     * Este nível é $minimum ou mais grave.
     *
     * @param \App\Application\System\Shared\LogLevel $minimum
     *
     * @return bool
     */
    public function isAtLeast(self $minimum): bool {
        return $this->severity() >= $minimum->severity();
    }

    /**
     * O nível pelo nome, sem diferenciar maiúsculas ("ERROR", "error");
     * null quando o nome não é um nível do PSR-3.
     *
     * @param string $name
     *
     * @return self|null
     */
    public static function fromName(string $name): ?self {
        return self::tryFrom(strtolower($name));
    }

    /**
     * Os nomes dos níveis (para o Choice dos Request DTOs).
     *
     * @return list<string>
     */
    public static function values(): array {
        return array_map(static fn(self $level): string => $level->value, self::cases());
    }
}
