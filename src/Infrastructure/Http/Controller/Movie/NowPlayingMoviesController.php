<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\UseCase\NowPlaying\GetNowPlayingMoviesUseCase;
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
 * Controller for getting now playing movies
 */
#[AsController]
class NowPlayingMoviesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\NowPlaying\GetNowPlayingMoviesUseCase $getNowPlayingMoviesUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface                  $translator
     */
    public function __construct(
        private readonly GetNowPlayingMoviesUseCase $getNowPlayingMoviesUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get now playing movies
     */
    #[
        Route(
            name: "api_movies_now_playing",
            defaults: [
                "_api_resource_class" => MoviesEntryPoint::class,
                "_api_operation_name" => "get_now_playing_movies",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt("page", 1));

        if ($page <= 0) {
            throw UserValidationException::invalidFieldValue(
                "page",
                $page,
                "positive integer",
            );
        }

        try {
            $response = $this->getNowPlayingMoviesUseCase->execute($page);

            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching now playing movies",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
