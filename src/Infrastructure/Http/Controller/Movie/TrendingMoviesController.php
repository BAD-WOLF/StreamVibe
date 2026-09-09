<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\UseCase\Trending\GetTrendingMoviesUseCase;
use App\Domain\Exception\UserValidationException;
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
 * Controller for getting trending movies
 */
#[AsController]
class TrendingMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Trending\GetTrendingMoviesUseCase $getTrendingMoviesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface               $translator
     */
    public function __construct(
        private GetTrendingMoviesUseCase $getTrendingMoviesUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get trending movies
     */
    #[
        Route(
            name: "api_movies_trending",
            defaults: [
                "_api_resource_class" => MoviesEntryPoint::class,
                "_api_operation_name" => "get_trending_movies",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $timeWindow = $request->query->get("time_window", "week");
        $page = (int) $request->query->get("page", 1);

        if (!in_array($timeWindow, ["day", "week"], true)) {
            throw UserValidationException::invalidFieldValue(
                "time_window",
                $timeWindow,
                "must be 'day' or 'week'",
            );
        }

        if ($page <= 0) {
            throw UserValidationException::invalidFieldValue(
                "page",
                $page,
                "positive integer",
            );
        }

        try {
            $response = $this->getTrendingMoviesUseCase->execute(
                $timeWindow,
                max(1, $page),
            );

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching trending movies",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
