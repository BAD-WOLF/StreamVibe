<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Recommendations;

use App\Application\Movie\UseCase\Recommendations\GetMovieRecommendationsUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Recommendations\RecommendationsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting movie recommendations
 *
 * NOTA (ago/2026): passou a usar GetMovieRecommendationsUseCase
 * dedicado, em vez de GetMovieDetailsUseCase.
 */
#[AsController]
class GetMovieRecommendationsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Recommendations\GetMovieRecommendationsUseCase $getMovieRecommendationsUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface                             $translator
     */
    public function __construct(
        private readonly GetMovieRecommendationsUseCase $getMovieRecommendationsUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get movie recommendations by ID
     */
    #[
        Route(
            name: "api_movies_recommendations",
            defaults: [
                "_api_resource_class" => RecommendationsEntryPoint::class,
                "_api_operation_name" => "get_movie_recommendations",
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

        try {
            $response = $this->getMovieRecommendationsUseCase->execute(
                $movieId,
            );

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching movie recommendations",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
