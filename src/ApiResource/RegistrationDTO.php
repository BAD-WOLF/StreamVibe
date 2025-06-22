<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\RegistrationController;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            status: 201,
            controller: RegistrationController::class,
            input: RegistrationInput::class,
            output: RegistrationOutput::class,
            name: 'post_register',
        ),
    ],
)]
final class RegistrationDTO
{
    // Classe vazia porque "Registration" é apenas um agrupador lógico.
    // Sua entrada e saída são definidas separadamente.
}

