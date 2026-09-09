<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Details;

use App\Application\Movie\DTO\Details\Request\GetMovieDetailsRequest;
use App\Application\Movie\UseCase\Details\GetMovieDetailsUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Details\DetailsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting movie details
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class GetMovieDetailsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Details\GetMovieDetailsUseCase $getMovieDetailsUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private readonly GetMovieDetailsUseCase $getMovieDetailsUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get movie details by ID
     */
    #[
        Route(
            name: "api_movies_details",
            defaults: [
                "_api_resource_class" => DetailsEntryPoint::class,
                "_api_operation_name" => "get_movie_details",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(int $movieId, Request $request): JsonResponse
    {
        if ($movieId <= 0) {
            throw UserValidationException::invalidFieldValue(
                "movie_id",
                $movieId,
                "positive integer",
            );
        }

        $detailsRequest = new GetMovieDetailsRequest(movieId: $movieId);

        try {
            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->getMovieDetailsUseCase->execute(
                request: $detailsRequest,
            );

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(
                dto: $response,
                status: $response->isSuccess() ? 200 : 400,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching movie details",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
