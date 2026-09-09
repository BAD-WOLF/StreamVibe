<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\UseCase\TopRated\GetTopRatedMoviesUseCase;
use App\Infrastructure\ApiPlatform\Movies\Search\MoviesEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting top rated movies
 */
#[AsController]
class TopRatedMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\TopRated\GetTopRatedMoviesUseCase $getTopRatedMoviesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface               $translator
     */
    public function __construct(
        private GetTopRatedMoviesUseCase $getTopRatedMoviesUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get top rated movies
     */
    #[
        Route(
            name: "api_movies_top_rated",
            defaults: [
                "_api_resource_class" => MoviesEntryPoint::class,
                "_api_operation_name" => "get_top_rated_movies",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query->get("page", 1);

        try {
            $response = $this->getTopRatedMoviesUseCase->execute(max(1, $page));

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching top rated movies",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
