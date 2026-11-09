<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Processes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\System\Processes\Model\ListProcessesOutput;
use App\Infrastructure\Http\Controller\System\Processes\ListProcessesController;

/**
 * GET /{_locale}/api/system/processes — os processos do contêiner
 * (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ListProcessesController, e o corpo de sucesso é o
 * ListProcessesOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/processes',
                status: 200,
                controller: ListProcessesController::class,
                openapi: new Operation(
                    operationId: 'SystemListProcesses',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the processes, by PID',
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
                    summary: 'List the processes of the container',
                    description: 'The processes the API sees (in Docker, those of its container), by PID: state, user, resident memory, CPU time, threads, start time and command line (secrets replaced by "***"). ROLE_DEV.',
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
                output: ListProcessesOutput::class,
                errors: [],
                name: 'get_system_processes',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ListProcessesEntryPoint {
}
