<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\Register;

use App\Application\Authentication\DTO\Register\Request\RegisterUserRequest;
use App\Application\Authentication\UseCase\Register\RegisterUserUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for user registration
 *
 * POST /{_locale}/api/register  {"email": "", "password": "", "agreeTerms": true}
 *
 * NOTA (out/2026):
 * - Era RegistrationController; renomeado para espelhar o
 *   RegisterUserUseCase/RegisterUserRequest/RegisterUserEntryPoint.
 * - Sem #[Route] aqui: a rota real é a operação do API Platform
 *   (RegisterUserEntryPoint). O #[Route] sem path registrava uma rota
 *   extra em "/".
 * - O corpo é lido pelo JsonBody: tipo errado no JSON ({"agreeTerms":
 *   "sim"}) responde 400 em vez de TypeError (500).
 * - Status fixo 201: o UseCase só devolve resposta de sucesso (erros são
 *   exceções). O `isSuccess() ? 201 : 400` saiu junto com o isSuccess()
 *   do DTO.
 * - A resposta sai pelo auto-mapping (RegisterUserOutput), não mais pelo
 *   fallback toArray().
 */
#[AsController]
final class RegisterUserController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\Register\RegisterUserUseCase $registerUserUseCase
     */
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = JsonBody::fromRequest($request);

        $response = $this->registerUserUseCase->execute(
            new RegisterUserRequest(
                email: $body->string("email"),
                password: $body->string("password"),
                agreeTerms: $body->bool("agreeTerms"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 201);
    }
}
