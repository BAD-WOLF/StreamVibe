<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\ResendVerification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Authentication\DTO\ResendVerification\Request\ResendVerificationEmailRequest;
use App\Infrastructure\ApiPlatform\Authentication\ResendVerification\Model\ResendVerificationEmailOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\ResendVerification\ResendVerificationEmailController;

/**
 * POST /{_locale}/api/resend-verification — manda de novo o email de
 * confirmação de uma conta ainda não verificada.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o ResendVerificationEmailController; o corpo da requisição é
 * o ResendVerificationEmailRequest e o de sucesso é o
 * ResendVerificationEmailOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/ResendVerification/ResendVerificationEntryPoint, que não
 *   tinha tag, summary nem respostas documentadas (só o _locale).
 * - É o único reenvio: o POST /api/verify/email/resend saiu (devolvia o
 *   link de verificação na resposta HTTP).
 * - input: o próprio ResendVerificationEmailRequest, no lugar do
 *   Model/ResendVerificationInput (que repetia o campo).
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/resend-verification',
                status: 200,
                controller: ResendVerificationEmailController::class,
                openapi: new Operation(
                    operationId: 'ResendVerificationEmail',
                    tags: ['Authentication'],
                    responses: [
                        200 => new Response(
                            description: 'OK — email_sent is true when the email was sent, and false when no account uses this email (generic message, so the answer does not tell whether the email is registered)',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid email or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Email: Please provide a valid email address'],
                            ),
                        ),
                        409 => new Response(
                            description: 'Conflict — the account is already verified',
                            content: new ErrorResponseSchema(
                                type: 'user_already_exists_error',
                                message: 'This account is already verified',
                            ),
                        ),
                        503 => new Response(
                            description: 'Service Unavailable — the email could not be sent',
                            content: new ErrorResponseSchema(
                                type: 'email_delivery_error',
                                message: 'Email delivery failed. Please try again later or contact support.',
                            ),
                        ),
                    ],
                    summary: 'Resend the email confirmation',
                    description: 'Sends again the email with the confirmation link of an account that is not verified yet. The previous links keep working until they expire.',
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
                input: ResendVerificationEmailRequest::class,
                output: ResendVerificationEmailOutput::class,
                errors: [],
                name: 'post_resend_verification',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class ResendVerificationEmailEntryPoint {
}
