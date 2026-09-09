<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\Register;

use App\Application\Authentication\DTO\Register\RegisterUserRequest;
use App\Application\Authentication\UseCase\RegisterUserUseCase;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\RateLimitExceededException;
use App\Infrastructure\ApiPlatform\Auth\Register\RegisterEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * Controller for user registration
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class RegistrationController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\RegisterUserUseCase $registerUserUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     */
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Register a new user
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[
        Route(
            name: "api_register",
            defaults: [
                "_api_resource_class" => RegisterEntryPoint::class,
                "_api_operation_name" => "post_register",
            ],
            methods: ["POST"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode(json: $request->getContent(), associative: true);

        if (!is_array(value: $data)) {
            throw UserValidationException::invalidFieldValue(
                "request_body",
                $data,
                "array",
            );
        }

        // Create DTO from request data
        $registerRequest = new RegisterUserRequest(
            email: $data["email"] ?? "",
            password: $data["password"] ?? "",
            agreeTerms: $data["agreeTerms"] ?? false,
        );

        // Execute use case - exceptions (UserValidationException,
        // UserAlreadyExistsException, UserNotFoundException,
        // EmailDeliveryException, etc.) are intentionally NOT caught here.
        // They propagate to Symfony's kernel.exception event, where
        // ExceptionListener maps each one to the correct HTTP status
        // (400/404/409/422/503...). Catching them here would collapse
        // every domain exception into a generic 500.
        $response = $this->registerUserUseCase->execute(
            request: $registerRequest,
        );

        // Use auto-mapping system to convert DTO to ApiPlatform Output Model
        return $this->safeMapToJsonResponse(
            dto: $response,
            status: $response->isSuccess() ? 201 : 400,
        );
    }
}

