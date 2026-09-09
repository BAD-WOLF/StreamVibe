<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\UseCase\Popular\GetPopularMoviesUseCase;
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
 * Controller for getting popular movies
 */
#[AsController]
class PopularMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Popular\GetPopularMoviesUseCase $getPopularMoviesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface             $translator
     */
    public function __construct(
        private GetPopularMoviesUseCase $getPopularMoviesUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get popular movies
     */
    #[
        Route(
            name: "api_movies_popular",
            defaults: [
                "_api_resource_class" => MoviesEntryPoint::class,
                "_api_operation_name" => "get_popular_movies",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query->get("page", 1);

        try {
            $response = $this->getPopularMoviesUseCase->execute(max(1, $page));

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching popular movies",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
