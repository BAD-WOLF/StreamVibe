<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\ValidateLink;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\ValidateLink\Model\ValidateEmailVerificationLinkOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\EmailVerification\ValidateLink\ValidateEmailVerificationLinkController;

/**
 * GET /{_locale}/api/verify/email/validate?expires=&id=&signature=&token=
 * — confere o link de confirmação sem verificar o email.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ValidateEmailVerificationLinkController, e o corpo de
 * sucesso é o ValidateEmailVerificationLinkOutput.
 *
 * NOTA (out/2026): criada quando a conferência saiu do
 * EmailVerificationController (#[Route("/api/verify/email/validate")],
 * fora do prefixo /{_locale}/api). Antes a resposta era sempre
 * valid=false (ver o NOTA do controller). Nome da rota mantido
 * (api_validate_verification_token).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/verify/email/validate',
                status: 200,
                controller: ValidateEmailVerificationLinkController::class,
                openapi: new Operation(
                    operationId: 'ValidateEmailVerificationLink',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — valid tells whether the link still works (an invalid or expired link is not an error here)',
                        ),
                        400 => new Response(
                            description: 'Bad Request — id is not a positive integer',
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
                    summary: 'Check a confirmation link without verifying the email',
                    description: 'Receives the query parameters of the confirmation link (the link of GET /verify/email) and tells whether it is still valid, without verifying the email. Call it with the same locale prefix as the link: the signature covers the whole link.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'expires',
                            in: 'query',
                            description: 'Expiration of the link (Unix timestamp)',
                            required: true,
                            schema: ['type' => 'integer'],
                        ),
                        new Parameter(
                            name: 'id',
                            in: 'query',
                            description: 'Id of the user',
                            required: true,
                            schema: ParameterSchema::USER_ID,
                        ),
                        new Parameter(
                            name: 'signature',
                            in: 'query',
                            description: 'Signature of the link',
                            required: true,
                            schema: ['type' => 'string'],
                        ),
                        new Parameter(
                            name: 'token',
                            in: 'query',
                            description: 'Token tied to the user id and email',
                            required: true,
                            schema: ['type' => 'string'],
                        ),
                    ],
                ),
                output: ValidateEmailVerificationLinkOutput::class,
                errors: [],
                name: 'api_validate_verification_token',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ValidateEmailVerificationLinkEntryPoint {
}
