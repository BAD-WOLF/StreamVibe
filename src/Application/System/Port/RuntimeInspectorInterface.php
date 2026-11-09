<?php

declare(strict_types = 1);

namespace App\Application\System\Port;

use App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo;
use App\Application\System\DTO\Runtime\Response\Model\PhpInfo;
use App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo;

/**
 * Porta para o runtime que atende a API: o PHP (versão, limites,
 * extensões), o OPcache e o Symfony (versão, ambiente, bundles). A
 * implementação é Infrastructure\System\PhpRuntimeInspector.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface RuntimeInspectorInterface {
    /**
     * @return \App\Application\System\DTO\Runtime\Response\Model\PhpInfo
     */
    public function php(): PhpInfo;

    /**
     * null quando a extensão não está instalada.
     *
     * @return \App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo|null
     */
    public function opcache(): ?OpcacheInfo;

    /**
     * @return \App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo
     */
    public function symfony(): SymfonyInfo;
}
