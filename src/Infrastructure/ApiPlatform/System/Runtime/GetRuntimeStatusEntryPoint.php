<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\System\Runtime\Model\GetRuntimeStatusOutput;
use App\Infrastructure\Http\Controller\System\Runtime\GetRuntimeStatusController;

/**
 * GET /{_locale}/api/system/runtime — o PHP, o OPcache e o Symfony
 * (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetRuntimeStatusController, e o corpo de sucesso é o
 * GetRuntimeStatusOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/runtime',
                status: 200,
                controller: GetRuntimeStatusController::class,
                openapi: new Operation(
                    operationId: 'SystemGetRuntime',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — PHP, OPcache and Symfony',
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
                    summary: 'Get the PHP and Symfony runtime',
                    description: 'The PHP that serves the API (version, SAPI, php.ini limits in bytes and seconds, extensions), OPcache (null when the extension is not installed) and Symfony (version, end of maintenance and of life, environment, debug, bundles). ROLE_DEV.',
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
                output: GetRuntimeStatusOutput::class,
                errors: [],
                name: 'get_system_runtime',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetRuntimeStatusEntryPoint {
}
