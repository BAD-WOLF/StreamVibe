<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Logs\Read;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\ApiPlatform\System\Logs\Read\Model\ReadLogFileOutput;
use App\Infrastructure\Http\Controller\System\Logs\Read\ReadLogFileController;

/**
 * GET /{_locale}/api/system/logs/{file} — as últimas linhas de um arquivo
 * de log, com filtros (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ReadLogFileController, e o corpo de sucesso é o
 * ReadLogFileOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/logs/{file}',
                requirements: [
                    'file' => RouteRequirement::PATH_SEGMENT,
                ],
                status: 200,
                controller: ReadLogFileController::class,
                openapi: new Operation(
                    operationId: 'SystemReadLogFile',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the file and its last lines that pass the filters, newest first',
                        ),
                        400 => new Response(
                            description: 'Bad Request — the file is not the name of a log file, or limit or level is invalid',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Limit: Limit must be between 1 and 1000'],
                            ),
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
                        404 => new Response(
                            description: 'Not Found — there is no log file with this name',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'Log file with identifier "old.log" not found',
                            ),
                        ),
                    ],
                    summary: 'Read a log file',
                    description: 'The last lines of a log file that pass the filters, newest first. The file is read from the end, and the reading stops when `limit` lines are found or after 50,000 lines are read (complete false = older lines were not read). Messages come with secrets replaced by "***" — also before the search — and are cut at 4,000 characters. ROLE_DEV.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'file',
                            in: 'path',
                            description: 'Name of the log file (as in GET /system/logs).',
                            required: true,
                            schema: ['type' => 'string', 'pattern' => '^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$', 'example' => 'prod.log'],
                        ),
                        new Parameter(
                            name: 'limit',
                            in: 'query',
                            description: 'How many lines, at most (1 to 1000).',
                            required: false,
                            schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => 1000, 'default' => 200],
                        ),
                        new Parameter(
                            name: 'level',
                            in: 'query',
                            description: 'Only the lines of this level or above (lines without a level are left out).',
                            required: false,
                            schema: ['type' => 'string', 'enum' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']],
                        ),
                        new Parameter(
                            name: 'search',
                            in: 'query',
                            description: 'Only the lines whose message contains this text (case-insensitive).',
                            required: false,
                            schema: ['type' => 'string', 'maxLength' => 200],
                        ),
                    ],
                ),
                output: ReadLogFileOutput::class,
                errors: [],
                name: 'get_system_log_file',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ReadLogFileEntryPoint {
}
