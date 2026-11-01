<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify\Model\VerifyEmailOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\EmailVerification\Verify\VerifyEmailController;

/**
 * GET /{_locale}/api/verify/email?expires=&id=&signature=&token= — o link
 * do email de confirmação: confere a assinatura e marca a conta como
 * verificada.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o VerifyEmailController, e o corpo de sucesso é o
 * VerifyEmailOutput.
 *
 * - ROUTE_NAME: a rota para a qual o EmailVerifier gera o link assinado
 *   (e que o ValidateEmailVerificationLinkController reconstrói);
 * - LINK_PARAMETERS: os parâmetros de query do link — `id` (nosso) e
 *   `expires`, `signature` e `token` (do VerifyEmailBundle).
 *
 * NOTA (out/2026): criada quando a verificação saiu do
 * EmailVerificationController. Antes a rota era um #[Route] em
 * "/api/verify/email" — fora do prefixo /{_locale}/api, sem documentação
 * no Swagger e com um JSON de erro próprio. Agora segue o padrão das
 * outras rotas (e é pública pela regra de /verify no security.yaml). O
 * nome da rota (api_verify_email) é o mesmo de antes.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/verify/email',
                status: 200,
                controller: VerifyEmailController::class,
                openapi: new Operation(
                    operationId: 'VerifyEmail',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — email verified (already_verified is true when it was verified before; opening the link again is not an error)',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid, expired or tampered link, a link of another account, or id that is not a positive integer',
                            content: new ErrorResponseSchema(
                                type: 'invalid_verification_link_error',
                                message: 'The link to verify your email has expired. Please request a new link.',
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
                    summary: 'Verify the email address (confirmation link)',
                    description: 'Target of the link sent by POST /register and POST /resend-verification. All query parameters come from the link and are covered by its signature.',
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
                output: VerifyEmailOutput::class,
                errors: [],
                name: self::ROUTE_NAME,
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class VerifyEmailEntryPoint {
    public const string ROUTE_NAME = 'api_verify_email';

    /**
     * @var list<string>
     */
    public const array LINK_PARAMETERS = ['expires', 'id', 'signature', 'token'];
}
