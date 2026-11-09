<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Logs\Read;

use App\Application\Shared\Port\SecretMaskerInterface;
use App\Application\Shared\RequestValidator;
use App\Application\System\DTO\Logs\Read\Request\ReadLogFileRequest;
use App\Application\System\DTO\Logs\Read\Response\Model\LogEntry;
use App\Application\System\DTO\Logs\Read\Response\ReadLogFileResponse;
use App\Application\System\Port\LogReaderInterface;
use App\Application\System\Shared\LogLevel;
use App\Domain\Exception\BusinessLogicException;
use Generator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * As últimas linhas de um arquivo de log (GET /system/logs/{file},
 * ROLE_DEV), da mais nova para a mais antiga, com os filtros de nível
 * mínimo e de texto.
 *
 * O arquivo é lido do fim para o começo e a leitura para assim que junta
 * `limit` linhas, ou depois de ler MAX_SCANNED_LINES — um arquivo grande
 * não segura a requisição. `complete` diz se ele foi lido até o começo.
 *
 * Toda mensagem passa pelo SecretMasker ANTES da busca: buscar pelo texto
 * de um segredo não encontra nada (senão a busca diria, letra por letra,
 * se um palpite está certo). Depois a mensagem é cortada em
 * MAX_MESSAGE_LENGTH caracteres.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class ReadLogFileUseCase {
    /**
     * Linhas lidas, no máximo, por pedido.
     */
    public const int MAX_SCANNED_LINES = 50_000;

    /**
     * Tamanho máximo (em caracteres) da mensagem de uma linha.
     */
    public const int MAX_MESSAGE_LENGTH = 4_000;

    /**
     * @param \App\Application\System\Port\LogReaderInterface    $logs
     * @param \App\Application\Shared\Port\SecretMaskerInterface $secretMasker
     * @param \App\Application\Shared\RequestValidator           $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        private LogReaderInterface $logs,
        private SecretMaskerInterface $secretMasker,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\System\DTO\Logs\Read\Request\ReadLogFileRequest $request
     *
     * @return \App\Application\System\DTO\Logs\Read\Response\ReadLogFileResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException arquivo que não está na pasta de logs (404)
     */
    public function execute(ReadLogFileRequest $request): ReadLogFileResponse {
        $this->requestValidator->validate($request, 'system_log_file_request', ['file' => $request->file]);

        $file = $this->logs->file($request->file);

        if ($file === null) {
            throw BusinessLogicException::notFound('Log file', $request->file);
        }

        $minimum = $request->level === null ? null : LogLevel::from($request->level);
        $search = trim((string) $request->search);
        $lines = $this->linesOf($request->file);
        $entries = [];
        $scanned = 0;
        $complete = true;

        while ($lines->valid()) {
            if ($scanned === self::MAX_SCANNED_LINES) {
                $complete = false;
                break;
            }

            $entry = $this->matching($lines->current(), $minimum, $search);
            $scanned++;
            $lines->next();

            if ($entry === null) {
                continue;
            }

            $entries[] = $entry;

            if (count($entries) === $request->limit) {
                $complete = !$lines->valid();
                break;
            }
        }

        return ReadLogFileResponse::success(
            file: $file,
            entries: $entries,
            scannedLines: $scanned,
            complete: $complete,
            message: $this->translator->trans('Log file read successfully'),
        );
    }

    /**
     * @param string $file
     *
     * @return \Generator<int, \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>
     */
    private function linesOf(string $file): Generator {
        yield from $this->logs->entries($file);
    }

    /**
     * A linha pronta para sair (segredos trocados, mensagem cortada), ou
     * null quando ela não passa nos filtros.
     *
     * @param \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry $entry
     * @param \App\Application\System\Shared\LogLevel|null                  $minimum
     * @param string                                                        $search  vazio = sem busca
     *
     * @return \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry|null
     */
    private function matching(LogEntry $entry, ?LogLevel $minimum, string $search): ?LogEntry {
        if ($minimum !== null) {
            $level = $entry->level === null ? null : LogLevel::fromName($entry->level);

            if ($level === null || !$level->isAtLeast($minimum)) {
                return null;
            }
        }

        $message = $this->secretMasker->mask($entry->message);

        if ($search !== '' && mb_stripos($message, $search) === false) {
            return null;
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH - 1) . '…';
        }

        return $entry->withMessage($message);
    }
}
