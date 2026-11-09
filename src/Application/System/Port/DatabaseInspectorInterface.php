<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

use App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus;

/**
 * Porta para o estado do banco de dados: se responde e em quanto tempo, a
 * versão, o tamanho, as conexões abertas e as migrations. A implementação
 * é Infrastructure\Persistence\DoctrineDatabaseInspector.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface DatabaseInspectorInterface {
    /**
     * Confere o banco agora. Nunca lança exceção: uma falha de conexão
     * vem como reachable false, com o motivo em error.
     *
     * @return \App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus
     */
    public function inspect(): DatabaseStatus;
}
