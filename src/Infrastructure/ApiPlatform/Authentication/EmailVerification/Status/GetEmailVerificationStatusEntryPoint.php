<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Status;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Status\Model\GetEmailVerificationStatusOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Authentication\EmailVerification\Status\GetEmailVerificationStatusController;

/**
 * GET /{_locale}/api/verify/email/status/{userId} — se a conta já
 * verificou o email (pública: quem espera a verificação ainda não
 * consegue logar).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetEmailVerificationStatusController, e o corpo de sucesso é
 * o GetEmailVerificationStatusOutput.
 *
 * NOTA (out/2026): criada quando a consulta saiu do
 * EmailVerificationController (#[Route("/api/verify/email/status/{id}")],
 * fora do prefixo /{_locale}/api). A resposta não traz mais o email da
 * conta. Nome da rota mantido (api_verify_email_status); requirement
 * numérico via RouteRequirement::INTEGER, como nas outras rotas com id.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/verify/email/status/{userId}',
                requirements: [
                    'userId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetEmailVerificationStatusController::class,
                openapi: new Operation(
                    operationId: 'GetEmailVerificationStatus',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — verified tells whether the account confirmed its email',
                        ),
                        400 => new Response(
                            description: 'Bad Request — userId is not a positive integer',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['User_id: User ID must be a positive integer'],
                            ),
                        ),
                        404 => new Response(
                            description: 'Not Found — no user with this id',
                            content: new ErrorResponseSchema(
                                type: 'user_not_found_error',
                                message: 'User with ID 42 not found',
                            ),
                        ),
                    ],
                    summary: 'Check whether an account verified its email',
                    description: 'Meant for the client to poll after POST /register (user_id comes in its answer) until the user opens the confirmation link.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'userId',
                            in: 'path',
                            description: 'Id of the user (user_id of POST /register)',
                            required: true,
                            schema: ParameterSchema::USER_ID,
                        ),
                    ],
                ),
                output: GetEmailVerificationStatusOutput::class,
                errors: [],
                name: 'api_verify_email_status',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetEmailVerificationStatusEntryPoint {
}
