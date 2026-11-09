<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Server;

use App\Application\System\DTO\Server\Response\GetServerStatusResponse;
use App\Application\System\Port\ServerInspectorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * O servidor em que a API roda (GET /system/server, ROLE_DEV): a máquina,
 * o contêiner e os discos, lidos na hora.
 *
 * Não tem Request DTO: o endpoint não recebe nada, e um DTO vazio seria
 * só cerimônia.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class GetServerStatusUseCase {
    /**
     * @param \App\Application\System\Port\ServerInspectorInterface $server
     * @param \Symfony\Contracts\Translation\TranslatorInterface    $translator
     */
    public function __construct(
        private ServerInspectorInterface $server,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return \App\Application\System\DTO\Server\Response\GetServerStatusResponse
     */
    public function execute(): GetServerStatusResponse {
        return GetServerStatusResponse::success(
            host: $this->server->host(),
            container: $this->server->container(),
            disks: $this->server->disks(),
            message: $this->translator->trans('Server status retrieved successfully'),
        );
    }
}
