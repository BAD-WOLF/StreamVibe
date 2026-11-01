<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\CheckStatus;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Authentication\ResetPassword\CheckStatus\Model\CheckPasswordResetStatusOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\CheckStatus\CheckPasswordResetStatusController;

/**
 * GET /{_locale}/api/reset-password/status/{token} — se o token do link
 * de troca de senha ainda vale.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o CheckPasswordResetStatusController, e o corpo de sucesso é
 * o CheckPasswordResetStatusOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/ResetPassword/CheckStatus/CheckStatusEntryPoint; renomeado
 *   para espelhar o CheckPasswordResetStatusUseCase.
 * - Erros no formato real do ExceptionListener, no lugar de
 *   InvalidTokenSchema, NotFoundSchema, GoneSchema e
 *   InternalServerErrorSchema. Saiu o 404: token inexistente sempre foi
 *   400 (é o ResetPasswordBundle que diz "inválido"), como o
 *   ResetPasswordApiTest já conferia.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/reset-password/status/{token}',
                status: 200,
                controller: CheckPasswordResetStatusController::class,
                openapi: new Operation(
                    operationId: 'CheckResetPasswordStatus',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the token is valid until expires_at',
                        ),
                        400 => new Response(
                            description: 'Bad Request — malformed, unknown, cancelled or already used token',
                            content: new ErrorResponseSchema(
                                type: 'invalid_reset_token_error',
                                message: 'The reset password link is invalid. Please try to reset your password again.',
                            ),
                        ),
                        410 => new Response(
                            description: 'Gone — the token expired',
                            content: new ErrorResponseSchema(
                                type: 'expired_reset_token_error',
                                message: 'The password reset token has expired',
                                actionRequired: 'request_new_token',
                            ),
                        ),
                    ],
                    summary: 'Check a password reset token',
                    description: 'Tells whether the token of the password reset link can still be used with POST /reset-password/confirm.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'token',
                            in: 'path',
                            description: 'Token of the password reset link (40 characters)',
                            required: true,
                            schema: ['type' => 'string'],
                        ),
                    ],
                ),
                output: CheckPasswordResetStatusOutput::class,
                errors: [],
                name: 'get_reset_password_status',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class CheckPasswordResetStatusEntryPoint {
}
