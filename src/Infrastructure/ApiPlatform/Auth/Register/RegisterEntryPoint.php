<?php
declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\Register;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Infrastructure\ApiPlatform\Auth\Register\Model\RegisterUserInput;
use App\Infrastructure\ApiPlatform\Auth\Register\Model\RegisterUserResponse;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\Http\Controller\Authentication\Register\RegistrationController;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\BadRequestSchema;
use App\Infrastructure\ApiPlatform\Auth\Register\Schema\UnprocessableEntitySchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InternalServerErrorSchema;
use App\Infrastructure\ApiPlatform\Auth\Register\Schema\ServiceUnavailableSchema;
use App\Infrastructure\ApiPlatform\Auth\Register\Schema\ConflictSchema;
use App\Infrastructure\ApiPlatform\Auth\Register\Schema\NotFoundSchema;

/**
 *
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            status: 201,
            controller: RegistrationController::class,
            openapi: new Operation(
                operationId: 'registerUser',
                tags: ['Authentication'],
                responses: [
                    400 => new Response(
                        description: 'Bad Request — Invalid JSON data or arguments',
                        content: new BadRequestSchema()
                    ),
                    404 => new Response(
                        description: 'Not Found — User not found',
                        content: new NotFoundSchema()
                    ),
                    409 => new Response(
                        description: 'Conflict — User already exists',
                        content: new ConflictSchema()
                    ),
                    422 => new Response(
                        description: 'Unprocessable Entity — Invalid or missing data',
                        content: new UnprocessableEntitySchema()
                    ),
                    503 => new Response(
                        description: 'Service Unavailable — Email delivery failure',
                        content: new ServiceUnavailableSchema()
                    ),
                    500 => new Response(
                        description: 'Internal Server Error — Unexpected failure during registration',
                        content: new InternalServerErrorSchema()
                    ),
                ],
                summary: 'Register a new user',
                description: 'Handles user registration and email verification dispatch',
                parameters: [
                    (new Parameter(
                        name: '_locale',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'default' => 'pt_BR',
                        ]
                    )),
                ]
            ),
            input: RegisterUserInput::class,
            output: RegisterUserResponse::class,
            name: 'post_register',
        ),
    ],
)]
final class RegisterEntryPoint {
    // Empty class, because "Register" is just a logical grouping.
    // Its input and output are defined separately.
}
