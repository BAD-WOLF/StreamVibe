<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Confirm;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Authentication\DTO\ResetPassword\Confirm\Request\ConfirmPasswordResetRequest;
use App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Confirm\Model\ConfirmPasswordResetOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\Confirm\ConfirmPasswordResetController;

/**
 * POST /{_locale}/api/reset-password/confirm — define a senha nova com o
 * token do link de troca de senha.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ConfirmPasswordResetController; o corpo da requisição é o
 * ConfirmPasswordResetRequest e o de sucesso é o
 * ConfirmPasswordResetOutput.
 *
 * NOTA (out/2026): endpoint novo — não havia como definir a senha nova.
 * Público como o resto do fluxo (quem esqueceu a senha não está logado);
 * ver a regra de /reset-password no security.yaml.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/reset-password/confirm',
                status: 200,
                controller: ConfirmPasswordResetController::class,
                openapi: new Operation(
                    operationId: 'ConfirmPasswordReset',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — password changed. The link stops working and every refresh token of the user is revoked (other sessions must log in again once their access token expires).',
                        ),
                        400 => new Response(
                            description: 'Bad Request — password that breaks the rules (same as POST /register), missing fields, invalid or already used token',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Password: Password must be at least 12 characters long'],
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
                    summary: 'Set a new password with a reset token',
                    description: 'Sets the new password of the account of the token (the token comes in the link of POST /reset-password). The token can be used once.',
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
                input: ConfirmPasswordResetRequest::class,
                output: ConfirmPasswordResetOutput::class,
                errors: [],
                name: 'post_confirm_reset_password',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ConfirmPasswordResetEntryPoint {
}
