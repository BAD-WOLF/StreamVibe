<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\System\Shared;

use App\Application\System\Shared\LogLevel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as Psr3LogLevel;
use ReflectionClass;

/**
 * LogLevel: os níveis do PSR-3 em ordem de gravidade, o filtro de nível
 * mínimo e a leitura do nome como vem nos arquivos ("ERROR").
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class LogLevelTest extends TestCase
{
    public function testTheLevelsGoFromDebugToEmergency(): void
    {
        self::assertSame(['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], LogLevel::values());
        self::assertSame(range(0, 7), array_map(static fn(LogLevel $level): int => $level->severity(), LogLevel::cases()));
    }

    public function testTheLevelsAreThoseOfPsr3(): void
    {
        $psr3 = array_values((new ReflectionClass(Psr3LogLevel::class))->getConstants());

        self::assertEqualsCanonicalizing($psr3, LogLevel::values());
    }

    public function testAMinimumLetsItselfAndTheMoreSevereThrough(): void
    {
        self::assertTrue(LogLevel::Warning->isAtLeast(LogLevel::Warning));
        self::assertTrue(LogLevel::Emergency->isAtLeast(LogLevel::Warning));
        self::assertFalse(LogLevel::Notice->isAtLeast(LogLevel::Warning));
        self::assertTrue(LogLevel::Debug->isAtLeast(LogLevel::Debug));
    }

    public function testTheNameIsReadWithoutCase(): void
    {
        self::assertSame(LogLevel::Error, LogLevel::fromName('ERROR'));
        self::assertSame(LogLevel::Critical, LogLevel::fromName('Critical'));
        self::assertSame(LogLevel::Info, LogLevel::fromName('info'));
        self::assertNull(LogLevel::fromName('WARN'));
        self::assertNull(LogLevel::fromName(''));
    }
}
