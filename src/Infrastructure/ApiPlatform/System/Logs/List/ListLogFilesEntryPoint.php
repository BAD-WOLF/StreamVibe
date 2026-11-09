<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Logs\List;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\System\Logs\List\Model\ListLogFilesOutput;
use App\Infrastructure\Http\Controller\System\Logs\List\ListLogFilesController;

/**
 * GET /{_locale}/api/system/logs — os arquivos de log da aplicação
 * (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ListLogFilesController, e o corpo de sucesso é o
 * ListLogFilesOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/logs',
                status: 200,
                controller: ListLogFilesController::class,
                openapi: new Operation(
                    operationId: 'SystemListLogFiles',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the folder and the log files, most recently modified first',
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired, or the account is blocked',
                            content: new UnauthorizedSchema(),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the user of the JWT is not ROLE_DEV (http_error "Access Denied. The user doesn\'t have ROLE_DEV.")',
                            content: new ErrorResponseSchema(
                                type: 'http_error',
                                message: 'Access Denied. The user doesn\'t have ROLE_DEV.',
                            ),
                        ),
                    ],
                    summary: 'List the log files',
                    description: 'The log files of the application (var/log), most recently modified first. <environment>.log is written by the application; <environment>.log.1 is the previous one, kept when the current one passed 10 MiB. ROLE_DEV.',
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
                output: ListLogFilesOutput::class,
                errors: [],
                name: 'get_system_logs',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ListLogFilesEntryPoint {
}
