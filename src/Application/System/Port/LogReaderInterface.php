<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

use App\Application\System\DTO\Shared\Response\Model\LogFileSummary;

/**
 * Porta para os arquivos de log da aplicação (a pasta var/log). A
 * implementação é Infrastructure\Logging\LogFileReader.
 *
 * A leitura é do fim para o começo e preguiçosa (entries() é um gerador):
 * quem lê decide quando parar — o ReadLogFileUseCase para quando junta as
 * linhas que quer. Filtro, segredos e limites são regra do UseCase; aqui é
 * só arquivo.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface LogReaderInterface {
    /**
     * A pasta dos logs (para o painel dizer onde eles estão).
     *
     * @return string
     */
    public function directory(): string;

    /**
     * Os arquivos de log (*.log e os rotacionados, *.log.1), do alterado
     * por último ao mais antigo. Pasta inexistente: lista vazia.
     *
     * @return list<\App\Application\System\DTO\Shared\Response\Model\LogFileSummary>
     */
    public function files(): array;

    /**
     * Um arquivo da lista pelo nome; null quando não existe (ou o nome não
     * é de um arquivo da pasta de logs).
     *
     * @param string $name
     *
     * @return \App\Application\System\DTO\Shared\Response\Model\LogFileSummary|null
     */
    public function file(string $name): ?LogFileSummary;

    /**
     * As linhas do arquivo, da última para a primeira, já interpretadas
     * (data, canal, nível e mensagem; uma linha em outro formato vem com
     * só a mensagem). Nome que não está na lista: nenhuma linha.
     *
     * @param string $name
     *
     * @return iterable<int, \App\Application\System\DTO\Logs\Read\Response\Model\LogEntry>
     */
    public function entries(string $name): iterable;
}
