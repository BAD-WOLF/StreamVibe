<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Similar;

use App\Application\Movie\UseCase\Similar\GetMovieSimilarUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Similar\SimilarEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting similar movies
 *
 * NOTA (ago/2026): passou a usar GetMovieSimilarUseCase dedicado, em
 * vez de GetMovieDetailsUseCase.
 */
#[AsController]
class GetMovieSimilarController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Similar\GetMovieSimilarUseCase $getMovieSimilarUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private readonly GetMovieSimilarUseCase $getMovieSimilarUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get similar movies by ID
     */
    #[
        Route(
            name: "api_movies_similar",
            defaults: [
                "_api_resource_class" => SimilarEntryPoint::class,
                "_api_operation_name" => "get_movie_similar",
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
            $response = $this->getMovieSimilarUseCase->execute($movieId);

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching similar movies",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
