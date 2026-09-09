<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person;

use App\Application\Person\DTO\Credits\GetPersonMovieCreditsRequest;
use App\Application\Person\UseCase\Credits\GetPersonMovieCreditsUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Person\Credits\PersonMovieCreditsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting person movie credits — endpoint dedicado,
 * separado de PersonDetailsController (ago/2026).
 */
#[AsController]
class PersonMovieCreditsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\GetPersonMovieCreditsUseCase $getPersonMovieCreditsUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface           $translator
     */
    public function __construct(
        private GetPersonMovieCreditsUseCase $getPersonMovieCreditsUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get person movie credits by ID
     */
    #[
        Route(
            name: "api_person_movie_credits",
            defaults: [
                "_api_resource_class" => PersonMovieCreditsEntryPoint::class,
                "_api_operation_name" => "get_person_movie_credits",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(int $personId, Request $request): JsonResponse
    {
        if ($personId <= 0) {
            throw UserValidationException::invalidFieldValue(
                "person_id",
                $personId,
                "positive integer",
            );
        }

        try {
            $creditsRequest = new GetPersonMovieCreditsRequest(
                personId: $personId,
            );

            $response = $this->getPersonMovieCreditsUseCase->execute(
                $creditsRequest,
            );

            return $this->safeMapToJsonResponse(
                dto: $response,
                status: $response->isSuccess() ? 200 : 400,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching person movie credits",
                    ),
                    "message" => $e->getMessage(),
                    "person_id" => $personId,
                ],
                500,
            );
        }
    }
}
