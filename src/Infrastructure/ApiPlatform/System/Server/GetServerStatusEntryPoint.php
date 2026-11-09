<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Server;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\System\Server\Model\GetServerStatusOutput;
use App\Infrastructure\Http\Controller\System\Server\GetServerStatusController;

/**
 * GET /{_locale}/api/system/server — a máquina, o contêiner e os discos
 * (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetServerStatusController, e o corpo de sucesso é o
 * GetServerStatusOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/server',
                status: 200,
                controller: GetServerStatusController::class,
                openapi: new Operation(
                    operationId: 'SystemGetServer',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the machine, the container (cgroup) and the disks',
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired, or the account is blocked',
                            content: new UnauthorizedSchema(),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the user of the JWT is not ROLE_DEV (http_error "Access Denied.")',
                            content: new ErrorResponseSchema(
                                type: 'http_error',
                                message: 'Access Denied.',
                            ),
                        ),
                    ],
                    summary: 'Get the status of the server',
                    description: 'The machine (hostname, system, kernel, CPUs, load average, memory, uptime), the limits and usage of the container (its cgroup) and the mounted disks, read from /proc and /sys/fs/cgroup at the time of the request. ROLE_DEV.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                    ],
                ),
                output: GetServerStatusOutput::class,
                errors: [],
                name: 'get_system_server',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetServerStatusEntryPoint {
}
