<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\Register;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Authentication\DTO\Register\Request\RegisterUserRequest;
use App\Infrastructure\ApiPlatform\Authentication\Register\Model\RegisterUserOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\Http\Controller\Authentication\Register\RegisterUserController;

/**
 * POST /{_locale}/api/register — cria a conta (não verificada) e manda o
 * email de confirmação.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o RegisterUserController; o corpo da requisição é o
 * RegisterUserRequest (o próprio Request DTO do Application, com as
 * constraints que a validação usa) e o de sucesso é o RegisterUserOutput.
 *
 * NOTA (out/2026):
 * - Era Auth/Register/RegisterEntryPoint; a pasta Auth virou
 *   Authentication (espelhando Application/Authentication) e a classe foi
 *   renomeada para espelhar o RegisterUserUseCase.
 * - input: o RegisterUserRequest no lugar do Model/RegisterUserInput, que
 *   repetia os campos sem as regras (o Swagger não mostrava, por exemplo,
 *   que a senha precisa de 12 caracteres). Uma fonte só para a validação
 *   e para a documentação.
 * - Respostas de erro no formato real do ExceptionListener
 *   (ErrorResponseSchema), no lugar de BadRequestSchema, NotFoundSchema,
 *   ConflictSchema, UnprocessableEntitySchema, ServiceUnavailableSchema e
 *   InternalServerErrorSchema. Saíram 404 e 422 (o cadastro nunca os
 *   devolve) e o 500.
 * - formats json e errors: [] como nas outras EntryPoints.
 * - Nome da rota e operationId mantidos (clientes gerados a partir do
 *   Swagger continuam com os mesmos nomes).
 */
#[
    ApiResource(
        operations: [
            new Post(
                uriTemplate: '/register',
                status: 201,
                controller: RegisterUserController::class,
                openapi: new Operation(
                    operationId: 'registerUser',
                    tags: ['Authentication'],
                    responses: [
                        201 => new Response(
                            description: 'Created — account created and confirmation email sent. Registering again an email whose account is not verified yet only resends the email (same answer, no second account).',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid fields (email, password rules, terms not accepted) or a body that is not a JSON object',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Password: Password must be at least 12 characters long'],
                            ),
                        ),
                        409 => new Response(
                            description: 'Conflict — the email belongs to an account that is already verified',
                            content: new ErrorResponseSchema(
                                type: 'user_already_exists_error',
                                message: 'A user with this email already exists',
                            ),
                        ),
                        503 => new Response(
                            description: 'Service Unavailable — the confirmation email could not be sent (the account is created; ask for a new email with POST /resend-verification)',
                            content: new ErrorResponseSchema(
                                type: 'email_delivery_error',
                                message: 'Email delivery failed. Please try again later or contact support.',
                            ),
                        ),
                    ],
                    summary: 'Register a new user',
                    description: 'Creates an unverified account and sends the email with the confirmation link (GET /verify/email). The account can log in only after the email is confirmed.',
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
                input: RegisterUserRequest::class,
                output: RegisterUserOutput::class,
                errors: [],
                name: 'post_register',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class RegisterUserEntryPoint {
}
