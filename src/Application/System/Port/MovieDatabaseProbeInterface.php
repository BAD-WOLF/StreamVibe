<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

use App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus;

/**
 * Porta para conferir se o banco de filmes (o TMDB) responde e aceita o
 * token da aplicação.
 *
 * Quem implementa é o mesmo adaptador do catálogo
 * (Infrastructure\ExternalServices\TmdbApiService, que também é a
 * MovieDatabaseGatewayInterface): ele continua sendo o único lugar do
 * projeto que conhece o HttpClient e o endereço do TMDB.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface MovieDatabaseProbeInterface {
    /**
     * Uma requisição curta ao TMDB, agora. Nunca lança exceção: sem
     * resposta, reachable false; resposta que não é 2xx, o status e o
     * motivo em error.
     *
     * @return \App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus
     */
    public function probe(): ExternalServiceStatus;
}
