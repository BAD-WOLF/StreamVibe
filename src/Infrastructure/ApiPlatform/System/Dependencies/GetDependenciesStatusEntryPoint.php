<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\System\Dependencies;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\System\Dependencies\Model\GetDependenciesStatusOutput;
use App\Infrastructure\Http\Controller\System\Dependencies\GetDependenciesStatusController;

/**
 * GET /{_locale}/api/system/dependencies — o banco de dados e o TMDB,
 * conferidos na hora (ROLE_DEV).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetDependenciesStatusController, e o corpo de sucesso é o
 * GetDependenciesStatusOutput.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/system/dependencies',
                status: 200,
                controller: GetDependenciesStatusController::class,
                openapi: new Operation(
                    operationId: 'SystemGetDependencies',
                    tags: ['System'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the database and TMDB (also when one of them is down)',
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
                    summary: 'Check the database and TMDB',
                    description: 'Checks the database (SELECT 1, version, size, open connections, migrations) and TMDB (GET /3/authentication, 5 s limit) at the time of the request. Always 200: a dependency that is down comes with reachable false and the error, with secrets replaced by "***". ROLE_DEV.',
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
                output: GetDependenciesStatusOutput::class,
                errors: [],
                name: 'get_system_dependencies',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetDependenciesStatusEntryPoint {
}
