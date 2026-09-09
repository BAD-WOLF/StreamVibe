<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie\Credits;

use App\Application\Movie\UseCase\Credits\GetMovieCreditsUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Movies\Credits\CreditsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting movie credits
 *
 * NOTA (ago/2026): passou a usar GetMovieCreditsUseCase dedicado, em
 * vez de GetMovieDetailsUseCase — elimina a chamada duplicada e
 * desnecessária a TmdbApiService::getMovieDetails() que ocorria antes
 * só para poder acessar os créditos.
 */
#[AsController]
class GetMovieCreditsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Movie\UseCase\Credits\GetMovieCreditsUseCase $getMovieCreditsUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private readonly GetMovieCreditsUseCase $getMovieCreditsUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get movie credits (cast and crew) by ID
     */
    #[
        Route(
            name: "api_movies_credits",
            defaults: [
                "_api_resource_class" => CreditsEntryPoint::class,
                "_api_operation_name" => "get_movie_credits",
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
            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->getMovieCreditsUseCase->execute($movieId);

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(
                dto: $response,
                status: 200,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while fetching movie credits",
                    ),
                    "message" => $e->getMessage(),
                    "movie_id" => $movieId,
                ],
                500,
            );
        }
    }
}
