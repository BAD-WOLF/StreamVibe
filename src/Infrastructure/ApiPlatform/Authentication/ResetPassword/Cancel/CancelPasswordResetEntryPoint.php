<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Cancel;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Authentication\DTO\ResetPassword\Cancel\Request\CancelPasswordResetRequest;
use App\Infrastructure\ApiPlatform\Authentication\ResetPassword\Cancel\Model\CancelPasswordResetOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\ResetPassword\Cancel\CancelPasswordResetController;

/**
 * POST /{_locale}/api/reset-password/cancel — cancela um pedido de troca
 * de senha (o link deixa de valer).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o CancelPasswordResetController; o corpo da requisição é o
 * CancelPasswordResetRequest e o de sucesso é o
 * CancelPasswordResetOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/ResetPassword/Cancel/CancelResetEntryPoint; renomeado para
 *   espelhar o CancelPasswordResetUseCase.
 * - input: o próprio CancelPasswordResetRequest, no lugar do
 *   Model/CancelResetInput.
 * - Erros no formato real do ExceptionListener, no lugar de
 *   BadRequestSchema, TokenNotFoundSchema e InternalServerErrorSchema.
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/reset-password/cancel',
                status: 200,
                controller: CancelPasswordResetController::class,
                openapi: new Operation(
                    operationId: 'CancelResetPassword',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — the link no longer works',
                        ),
                        400 => new Response(
                            description: 'Bad Request — missing token or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Token: Token cannot be blank'],
                            ),
                        ),
                        404 => new Response(
                            description: 'Not Found — no valid request for this token (unknown, expired, already used or already cancelled)',
                            content: new ErrorResponseSchema(
                                type: 'reset_token_not_found_error',
                                message: 'No reset request found for provided token',
                            ),
                        ),
                    ],
                    summary: 'Cancel a password reset request',
                    description: 'Cancels the password reset request of the token (e.g. "I did not ask for this" in the email).',
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
                input: CancelPasswordResetRequest::class,
                output: CancelPasswordResetOutput::class,
                errors: [],
                name: 'post_cancel_reset_password',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class CancelPasswordResetEntryPoint {
}
