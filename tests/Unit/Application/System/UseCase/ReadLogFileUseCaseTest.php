<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\System\UseCase;

use App\Application\Shared\Port\SecretMaskerInterface;
use App\Application\Shared\RequestValidator;
use App\Application\System\DTO\Logs\Read\Request\ReadLogFileRequest;
use App\Application\System\DTO\Logs\Read\Response\Model\LogEntry;
use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;
use App\Application\System\Port\LogReaderInterface;
use App\Application\System\UseCase\Logs\Read\ReadLogFileUseCase;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\UserValidationException;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

/**
 * ReadLogFileUseCase: as últimas linhas de um arquivo, da mais nova para a
 * mais antiga, com o nível mínimo e a busca; quando a leitura para
 * (limit, MAX_SCANNED_LINES) e o que `complete` diz; os segredos trocados
 * antes da busca; a mensagem cortada; o 404 e a validação.
 *
 * O leitor é falso: as linhas de cada arquivo já vêm da mais nova para a
 * mais antiga, e $this->read conta quantas foram pedidas (a leitura tem de
 * parar cedo). O mascarador falso troca "hunter2".
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ReadLogFileUseCaseTest extends TestCase
{
    private int $read = 0;

    public function testTheLastLinesComeNewestFirst(): void
    {
        $response = $this->useCase(['prod.log' => [$this->line('error', 'third'), $this->line('info', 'second'), $this->line('info', 'first')]])
            ->execute(new ReadLogFileRequest(file: 'prod.log', limit: 2));

        self::assertSame(['third', 'second'], $this->messages($response->entries));
        self::assertSame(2, $response->scannedLines);
        self::assertFalse($response->complete, 'the first line was not read');
        self::assertSame('prod.log', $response->file->name);
        self::assertSame('Log file read successfully', $response->message);
    }

    public function testCompleteWhenTheWholeFileWasRead(): void
    {
        $lines = [$this->line('info', 'second'), $this->line('info', 'first')];

        $all = $this->useCase(['prod.log' => $lines])->execute(new ReadLogFileRequest(file: 'prod.log'));
        $exactly = $this->useCase(['prod.log' => $lines])->execute(new ReadLogFileRequest(file: 'prod.log', limit: 2));
        $empty = $this->useCase(['prod.log' => []])->execute(new ReadLogFileRequest(file: 'prod.log'));

        self::assertSame([['second', 'first'], 2, true], [$this->messages($all->entries), $all->scannedLines, $all->complete]);
        self::assertSame([['second', 'first'], 2, true], [$this->messages($exactly->entries), $exactly->scannedLines, $exactly->complete]);
        self::assertSame([[], 0, true], [$empty->entries, $empty->scannedLines, $empty->complete]);
    }

    public function testTheLevelIsAMinimumAndLeavesOutLinesWithoutALevel(): void
    {
        $response = $this->useCase(['prod.log' => [
            $this->line('critical', 'disk full'),
            $this->line('info', 'matched route'),
            new LogEntry(datetime: null, channel: null, level: null, message: 'a line in another format'),
            $this->line('warning', 'slow query'),
            $this->line('debug', 'notified event'),
        ]])->execute(new ReadLogFileRequest(file: 'prod.log', level: 'warning'));

        self::assertSame(['disk full', 'slow query'], $this->messages($response->entries));
        self::assertSame(5, $response->scannedLines);
        self::assertTrue($response->complete);
    }

    public function testWithoutALevelEveryLineComes(): void
    {
        $response = $this->useCase(['prod.log' => [
            $this->line('debug', 'notified event'),
            new LogEntry(datetime: null, channel: null, level: null, message: 'a line in another format'),
        ]])->execute(new ReadLogFileRequest(file: 'prod.log'));

        self::assertSame(['notified event', 'a line in another format'], $this->messages($response->entries));
    }

    public function testTheSearchIgnoresCase(): void
    {
        $response = $this->useCase(['prod.log' => [
            $this->line('info', 'User ANA logged in'),
            $this->line('info', 'User bob logged in'),
            $this->line('info', 'Ana changed her password'),
        ]])->execute(new ReadLogFileRequest(file: 'prod.log', search: '  ana '));

        self::assertSame(['User ANA logged in', 'Ana changed her password'], $this->messages($response->entries));
    }

    /**
     * A busca vê o texto já mascarado: procurar o segredo não encontra a
     * linha que o tem — senão a busca diria se um palpite está certo.
     */
    public function testSecretsAreMaskedBeforeTheSearch(): void
    {
        $lines = ['prod.log' => [$this->line('error', 'login with password hunter2 failed')]];

        $bySecret = $this->useCase($lines)->execute(new ReadLogFileRequest(file: 'prod.log', search: 'hunter2'));
        $byText = $this->useCase($lines)->execute(new ReadLogFileRequest(file: 'prod.log', search: 'failed'));

        self::assertSame([], $bySecret->entries);
        self::assertSame(['login with password *** failed'], $this->messages($byText->entries));
    }

    public function testTheRestOfTheLineStaysAsItWas(): void
    {
        $entry = $this->useCase(['prod.log' => [new LogEntry(datetime: '2026-11-08T14:50:01.123+00:00', channel: 'app', level: 'error', message: 'boom')]])
            ->execute(new ReadLogFileRequest(file: 'prod.log'))
            ->entries[0];

        self::assertSame(['2026-11-08T14:50:01.123+00:00', 'app', 'error', 'boom'], [$entry->datetime, $entry->channel, $entry->level, $entry->message]);
    }

    public function testLongMessagesAreCut(): void
    {
        $message = str_repeat('é', ReadLogFileUseCase::MAX_MESSAGE_LENGTH + 500);

        $entry = $this->useCase(['prod.log' => [$this->line('info', $message)]])
            ->execute(new ReadLogFileRequest(file: 'prod.log'))
            ->entries[0];

        self::assertSame(ReadLogFileUseCase::MAX_MESSAGE_LENGTH, mb_strlen($entry->message));
        self::assertStringEndsWith('é…', $entry->message);
    }

    public function testTheReadingStopsAtTheScanLimit(): void
    {
        $response = $this->useCase(['big.log' => $this->manyLines(ReadLogFileUseCase::MAX_SCANNED_LINES + 10)])
            ->execute(new ReadLogFileRequest(file: 'big.log', level: 'error'));

        self::assertSame([], $response->entries);
        self::assertSame(ReadLogFileUseCase::MAX_SCANNED_LINES, $response->scannedLines);
        self::assertFalse($response->complete);
        self::assertLessThanOrEqual(ReadLogFileUseCase::MAX_SCANNED_LINES + 1, $this->read, 'the rest of the file was read');
    }

    public function testTheReadingStopsAtTheLimit(): void
    {
        $this->useCase(['big.log' => $this->manyLines(10_000)])->execute(new ReadLogFileRequest(file: 'big.log', limit: 3));

        self::assertSame(4, $this->read, 'three lines and one more, to know if the file ended');
    }

    public function testAFileThatIsNotThereIsANotFound(): void
    {
        try {
            $this->useCase(['prod.log' => []])->execute(new ReadLogFileRequest(file: 'old.log'));
            self::fail('A file that is not there should be a 404');
        } catch (BusinessLogicException $exception) {
            self::assertSame(404, $exception->getCode());
            self::assertSame('Log file with identifier "old.log" not found', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{\App\Application\System\DTO\Logs\Read\Request\ReadLogFileRequest, string}>
     */
    public static function invalidRequestProvider(): iterable
    {
        yield 'a path' => [new ReadLogFileRequest(file: '../../etc/passwd'), 'file'];
        yield 'a hidden file' => [new ReadLogFileRequest(file: '.env.log'), 'file'];
        yield 'empty' => [new ReadLogFileRequest(file: ''), 'file'];
        yield 'no lines' => [new ReadLogFileRequest(file: 'prod.log', limit: 0), 'limit'];
        yield 'too many lines' => [new ReadLogFileRequest(file: 'prod.log', limit: 1001), 'limit'];
        yield 'not a PSR-3 level' => [new ReadLogFileRequest(file: 'prod.log', level: 'warn'), 'level'];
        yield 'a search too long' => [new ReadLogFileRequest(file: 'prod.log', search: str_repeat('a', 201)), 'search'];
    }

    #[DataProvider('invalidRequestProvider')]
    public function testInvalidRequestsAreRejected(ReadLogFileRequest $request, string $field): void
    {
        try {
            $this->useCase(['prod.log' => [$this->line('info', 'x')]])->execute($request);
            self::fail('The request should be rejected');
        } catch (UserValidationException $exception) {
            self::assertArrayHasKey($field, $exception->getContext()['field_errors']);
            self::assertSame(0, $this->read, 'nothing should be read');
        }
    }

    /**
     * @param array<string, iterable<int, \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>> $files
     */
    private function useCase(array $files): ReadLogFileUseCase
    {
        $this->read = 0;
        $logs = new class($files, $this) implements LogReaderInterface {
            /**
             * @param array<string, iterable<int, \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>> $files
             */
            public function __construct(private array $files, private ReadLogFileUseCaseTest $test)
            {
            }

            public function directory(): string
            {
                return '/app/var/log';
            }

            public function files(): array
            {
                return [];
            }

            public function file(string $name): ?LogFileSummary
            {
                return isset($this->files[$name])
                    ? new LogFileSummary(name: $name, sizeBytes: 100, modifiedAt: new DateTimeImmutable('2026-11-08 14:50:01'))
                    : null;
            }

            public function entries(string $name): iterable
            {
                foreach ($this->files[$name] ?? [] as $entry) {
                    $this->test->countRead();
                    yield $entry;
                }
            }
        };

        $masker = new class implements SecretMaskerInterface {
            public function mask(string $text): string
            {
                return str_replace('hunter2', '***', $text);
            }
        };

        return new ReadLogFileUseCase(
            $logs,
            $masker,
            new RequestValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new NullLogger()),
            new IdentityTranslator(),
        );
    }

    /**
     * Chamado pelo leitor falso a cada linha entregue.
     */
    public function countRead(): void
    {
        $this->read++;
    }

    private function line(string $level, string $message): LogEntry
    {
        return new LogEntry(datetime: '2026-11-08T14:50:01.123+00:00', channel: 'app', level: $level, message: $message);
    }

    /**
     * @return \Generator<int, \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>
     */
    private function manyLines(int $count): Generator
    {
        for ($index = 0; $index < $count; $index++) {
            yield $this->line('info', "line {$index}");
        }
    }

    /**
     * @param list<\App\Application\System\DTO\Logs\Read\Response\Model\LogEntry> $entries
     *
     * @return list<string>
     */
    private function messages(array $entries): array
    {
        return array_map(static fn(LogEntry $entry): string => $entry->message, $entries);
    }
}
