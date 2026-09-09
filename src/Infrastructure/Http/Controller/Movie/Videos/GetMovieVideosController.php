<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Videos;

use App\Application\Movie\UseCase\Videos\GetMovieVideosUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Videos\VideosEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting movie videos
 *
 * NOTA (ago/2026): passou a usar GetMovieVideosUseCase dedicado, em
 * vez de GetMovieDetailsUseCase.
 */
#[AsController]
class GetMovieVideosController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Videos\GetMovieVideosUseCase $getMovieVideosUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     */
    public function __construct(
        private readonly GetMovieVideosUseCase $getMovieVideosUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get movie videos (trailers, teasers, clips) by ID
     */
    #[
        Route(
            name: "api_movies_videos",
            defaults: [
                "_api_resource_class" => VideosEntryPoint::class,
                "_api_operation_name" => "get_movie_videos",
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
            $response = $this->getMovieVideosUseCase->execute($movieId);

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching movie videos",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
