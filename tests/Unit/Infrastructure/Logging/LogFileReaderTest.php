<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Logging;

use App\Application\System\DTO\Logs\Read\Response\Model\LogEntry;
use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;
use App\Infrastructure\Logging\LogFileReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * LogFileReader: só arquivos de log da pasta (nada de links, pastas,
 * ocultos ou caminhos), do alterado por último ao mais antigo; as linhas
 * do fim para o começo, inclusive quando uma linha atravessa os blocos de
 * leitura; o formato do Monolog interpretado e o resto como texto.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class LogFileReaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/log-reader-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $name) {
            $path = "{$this->directory}/{$name}";

            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif ($name !== '.' && $name !== '..' && is_dir($path)) {
                rmdir($path);
            }
        }

        rmdir($this->directory);
    }

    public function testOnlyTheLogFilesOfTheFolderAreListedNewestFirst(): void
    {
        $this->write('prod.log', "a\n", '2026-11-08 12:00:00');
        $this->write('prod.log.1', "b\n", '2026-11-07 12:00:00');
        $this->write('doctrine-2026-11-08.log', "c\n", '2026-11-08 13:00:00');
        $this->write('.hidden.log', "d\n");
        $this->write('notes.txt', "e\n");
        mkdir("{$this->directory}/folder.log");
        symlink('/etc/hostname', "{$this->directory}/link.log");

        $files = $this->reader()->files();

        self::assertSame(['doctrine-2026-11-08.log', 'prod.log', 'prod.log.1'], array_map(static fn(LogFileSummary $file): string => $file->name, $files));
        self::assertSame([2, '2026-11-08T12:00:00+00:00'], [$files[1]->sizeBytes, $files[1]->modifiedAt->format(DATE_ATOM)]);
    }

    public function testAFolderThatDoesNotExistHasNoFiles(): void
    {
        $reader = new LogFileReader($this->directory . '/missing');

        self::assertSame([], $reader->files());
        self::assertSame($this->directory . '/missing', $reader->directory());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function namesOutsideTheFolderProvider(): iterable
    {
        yield 'a path up' => ['../prod.log'];
        yield 'an absolute path' => ['/etc/passwd'];
        yield 'a hidden file' => ['.hidden.log'];
        yield 'not a log' => ['notes.txt'];
        yield 'a folder' => ['folder.log'];
        yield 'a link' => ['link.log'];
        yield 'missing' => ['old.log'];
    }

    #[DataProvider('namesOutsideTheFolderProvider')]
    public function testOnlyAFileOfTheListCanBeRead(string $name): void
    {
        $this->write('.hidden.log', "[2026-11-08T12:00:00+00:00] app.ERROR: hidden\n");
        $this->write('notes.txt', "[2026-11-08T12:00:00+00:00] app.ERROR: notes\n");
        mkdir("{$this->directory}/folder.log");
        symlink('/etc/hostname', "{$this->directory}/link.log");

        self::assertNull($this->reader()->file($name));
        self::assertSame([], iterator_to_array($this->reader()->entries($name), false));
    }

    public function testTheLinesComeFromTheEnd(): void
    {
        $this->write('prod.log', "[2026-11-08T12:00:00.123456+00:00] request.INFO: first {\"route\":\"x\"} []\n\n[2026-11-08T12:00:01+00:00] app.ERROR: second\r\nnot in the Monolog format\n");

        self::assertSame([
            [null, null, null, 'not in the Monolog format'],
            ['2026-11-08T12:00:01.000+00:00', 'app', 'error', 'second'],
            ['2026-11-08T12:00:00.123+00:00', 'request', 'info', 'first {"route":"x"} []'],
        ], $this->entries('prod.log'));
    }

    public function testALineWithoutChannelOrWithAnUnknownLevel(): void
    {
        $this->write('prod.log', "[2026-11-08T12:00:00+00:00] WARNING: no channel\n[2026-11-08T12:00:00+00:00] app.LOUD: unknown level\n[yesterday] app.ERROR: no date\n");

        self::assertSame([
            [null, null, null, '[yesterday] app.ERROR: no date'],
            [null, null, null, '[2026-11-08T12:00:00+00:00] app.LOUD: unknown level'],
            ['2026-11-08T12:00:00.000+00:00', null, 'warning', 'no channel'],
        ], $this->entries('prod.log'));
    }

    /**
     * Linhas maiores que o bloco de leitura (64 KiB) e quebras de linha
     * bem na divisa entre dois blocos.
     */
    public function testLinesAcrossTheReadingBlocks(): void
    {
        $long = str_repeat('x', 150_000);
        $lines = [];
        for ($index = 0; $index < 3000; $index++) {
            $lines[] = "[2026-11-08T12:00:00+00:00] app.INFO: line {$index}";
        }
        $lines[1500] = "[2026-11-08T12:00:00+00:00] app.ERROR: {$long}";
        $this->write('prod.log', implode("\n", $lines) . "\n");

        $messages = array_column($this->entries('prod.log'), 3);

        self::assertCount(3000, $messages);
        self::assertSame('line 2999', $messages[0]);
        self::assertSame($long, $messages[1499]);
        self::assertSame('line 0', $messages[2999]);
    }

    public function testInvalidUtf8BecomesQuestionMarks(): void
    {
        $this->write('prod.log', "[2026-11-08T12:00:00+00:00] app.ERROR: bad \xC3\x28 byte\n");

        $message = $this->entries('prod.log')[0][3];

        self::assertTrue(mb_check_encoding($message, 'UTF-8'));
        self::assertSame('bad ?( byte', $message);
    }

    public function testTheReadingCanStopEarly(): void
    {
        $this->write('prod.log', "[2026-11-08T12:00:00+00:00] app.INFO: one\n[2026-11-08T12:00:00+00:00] app.INFO: two\n");

        foreach ($this->reader()->entries('prod.log') as $entry) {
            self::assertSame('two', $entry->message);
            break;
        }

        // o arquivo foi fechado: pode ser apagado e recriado
        unlink("{$this->directory}/prod.log");
        $this->write('prod.log', '');
        self::assertSame([], $this->entries('prod.log'));
    }

    private function reader(): LogFileReader
    {
        return new LogFileReader($this->directory);
    }

    /**
     * @return list<array{string|null, string|null, string|null, string}>
     */
    private function entries(string $name): array
    {
        return array_map(
            static fn(LogEntry $entry): array => [$entry->datetime, $entry->channel, $entry->level, $entry->message],
            iterator_to_array($this->reader()->entries($name), false),
        );
    }

    private function write(string $name, string $content, ?string $modifiedAt = null): void
    {
        $path = "{$this->directory}/{$name}";
        file_put_contents($path, $content);

        if ($modifiedAt !== null) {
            touch($path, (int) strtotime($modifiedAt . ' UTC'));
        }
    }
}
