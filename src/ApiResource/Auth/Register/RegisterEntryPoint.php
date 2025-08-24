<?php

namespace App\ApiResource\Auth\Register;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Auth\Register\Model\RegisterUserInput;
use App\ApiResource\Auth\Register\Model\RegisterUserResponse;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            status: 201,
            controller: \App\Controller\API\Auth\Register\RegistrationController::class,
            openapi: new Operation(
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
final class RegisterEntryPoint
{
    // Empty class, because "Register" is just a logical grouping.
    // Its input and output are defined separately.
}

