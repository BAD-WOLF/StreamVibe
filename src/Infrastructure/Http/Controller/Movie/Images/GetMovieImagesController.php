<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Images;

use App\Application\Movie\UseCase\Images\GetMovieImagesUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Images\ImagesEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting movie images
 *
 * NOTA (ago/2026): passou a usar GetMovieImagesUseCase dedicado, em
 * vez de GetMovieDetailsUseCase — elimina a chamada duplicada a
 * TmdbApiService::getMovieDetails() que ocorria antes só para poder
 * acessar as imagens.
 */
#[AsController]
class GetMovieImagesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Images\GetMovieImagesUseCase $getMovieImagesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     */
    public function __construct(
        private readonly GetMovieImagesUseCase $getMovieImagesUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get movie images (posters, backdrops, logos) by ID
     */
    #[
        Route(
            name: "api_movies_images",
            defaults: [
                "_api_resource_class" => ImagesEntryPoint::class,
                "_api_operation_name" => "get_movie_images",
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
            $response = $this->getMovieImagesUseCase->execute($movieId);

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching movie images",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
