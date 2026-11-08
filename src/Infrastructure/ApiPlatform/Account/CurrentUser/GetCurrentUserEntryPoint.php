<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Account\CurrentUser;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Account\CurrentUser\Model\GetCurrentUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Account\CurrentUser\GetCurrentUserController;

/**
 * GET /{_locale}/api/me — a conta de quem está logado (exige JWT).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetCurrentUserController, e o corpo de sucesso é o
 * GetCurrentUserOutput.
 *
 * NOTA (nov/2026): criada com o GET /me. Sem 404 documentado: o usuário
 * do JWT é recarregado do banco pelo firewall a cada requisição, e uma
 * conta que não existe mais já para ali, no 401.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/me',
                status: 200,
                controller: GetCurrentUserController::class,
                openapi: new Operation(
                    operationId: 'GetCurrentUser',
                    tags: ['Account'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the account of the user of the JWT',
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                    ],
                    summary: 'Get the account of the logged-in user',
                    description: 'Id, email, roles and email verification of the user of the JWT, read from the database on every call (a change after the login shows up at once).',
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
                output: GetCurrentUserOutput::class,
                errors: [],
                name: 'api_current_user',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetCurrentUserEntryPoint {
}
