<?php

namespace App\ApiResource\Auth\Register;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Auth\Register\Model\RegisterUserInput;
use App\ApiResource\Auth\Register\Model\RegisterUserResponse;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            status: 201,
            controller: \App\Controller\API\Auth\Register\RegistrationController::class,
            input: RegisterUserInput::class,
            output: RegisterUserResponse::class,
            name: 'post_register',
        ),
    ],
)]
final class RegisterEntryPoint
{
    // Classe vazia porque "Registration" é apenas um agrupador lógico.
    // Sua entrada e saída são definidas separadamente.
}

