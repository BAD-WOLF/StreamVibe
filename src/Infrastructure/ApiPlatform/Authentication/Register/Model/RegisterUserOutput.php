<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Authentication\Register\Model;

use App\Application\Authentication\DTO\Register\Response\RegisterUserResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de POST /{_locale}/api/register:
 *
 *     {
 *       "success": true,
 *       "data": {"user_id": 42},
 *       "message": "User registered successfully. Please check your email to verify your account."
 *     }
 *
 * Mapeado do RegisterUserResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * RegisterUserResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Auth/Register/Model/RegisterUserResponse (mesmo
 * nome do DTO do Application, importado com alias). Até aqui só
 * alimentava o Swagger — o JSON real saía do toArray() do DTO, sem
 * envelope. Saíram userId no nível de cima (agora em data.user_id),
 * success()/error() e o `success` variável (erro sai do
 * ExceptionListener).
 */
#[AutoMapFromDto(RegisterUserResponse::class)]
final readonly class RegisterUserOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Authentication\DTO\Register\Response\RegisterUserResponse $data
     * @param string|null                                                             $message
     */
    public function __construct(
        public RegisterUserResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
