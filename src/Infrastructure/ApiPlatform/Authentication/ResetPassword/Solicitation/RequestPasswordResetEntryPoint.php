<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Solicitation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Authentication\DTO\ResetPassword\Solicitation\Request\RequestPasswordResetRequest;
use App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Solicitation\Model\RequestPasswordResetOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\Solicitation\RequestPasswordResetController;

/**
 * POST /{_locale}/api/reset-password — manda por email o link para trocar
 * a senha.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o RequestPasswordResetController; o corpo da requisição é o
 * RequestPasswordResetRequest e o de sucesso é o
 * RequestPasswordResetOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/ResetPassword/Solicitation/SolicitationEntryPoint;
 *   renomeado para espelhar o RequestPasswordResetUseCase.
 * - input: o próprio RequestPasswordResetRequest, no lugar do
 *   Model/SolicitationInput.
 * - Erros no formato real do ExceptionListener (ErrorResponseSchema), no
 *   lugar de BadRequestSchema, ForbiddenSchema, ValidationErrorSchema,
 *   TooManyRequestsSchema e InternalServerErrorSchema. Saíram o 422 (o
 *   pedido nunca o devolve) e o 500; entrou o 503 (falha no envio do
 *   email, que antes era engolida e respondia 200).
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/reset-password',
                status: 200,
                controller: RequestPasswordResetController::class,
                openapi: new Operation(
                    operationId: 'ResetPasswordSolicitation',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — email_sent true: link sent (expires_at is its expiration); email_sent false with expires_at: a valid link was already sent (no new email); email_sent false without expires_at: no account uses this email (generic message)',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid email or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Email: Please provide a valid email address'],
                            ),
                        ),
                        403 => new Response(
                            description: 'Forbidden — the account has not verified its email yet',
                            content: new ErrorResponseSchema(
                                type: 'user_not_verified_error',
                                message: 'Please verify your email address before requesting a password reset.',
                                actionRequired: 'email_verification',
                            ),
                        ),
                        429 => new Response(
                            description: 'Too Many Requests — a link was requested moments ago (the Retry-After header tells how many seconds to wait)',
                            content: new ErrorResponseSchema(
                                type: 'rate_limit_exceeded_error',
                                message: 'Too many password reset requests. Please wait before trying again.',
                            ),
                        ),
                        503 => new Response(
                            description: 'Service Unavailable — the email could not be sent (no link is left behind)',
                            content: new ErrorResponseSchema(
                                type: 'email_delivery_error',
                                message: 'Email delivery failed. Please try again later or contact support.',
                            ),
                        ),
                    ],
                    summary: 'Request a password reset link',
                    description: 'Sends an email with the link to set a new password (the link opens the frontend, which then calls GET /reset-password/status/{token} and POST /reset-password/confirm).',
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
                input: RequestPasswordResetRequest::class,
                output: RequestPasswordResetOutput::class,
                errors: [],
                name: 'post_reset_password_solicitation',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class RequestPasswordResetEntryPoint {
}
