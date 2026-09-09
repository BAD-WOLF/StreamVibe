<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Person;

use App\Application\Person\DTO\Details\GetPersonDetailsRequest;
use App\Application\Person\UseCase\Details\GetPersonDetailsUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Person\Details\PersonDetailsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting person details
 *
 * NOTA (ago/2026): não busca mais movie_credits/tv_credits/images/
 * external_ids — movie credits ganhou endpoint dedicado
 * (GET /person/{id}/movie-credits), os outros três nunca tiveram
 * implementação real.
 */
#[AsController]
class PersonDetailsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Person\UseCase\Details\GetPersonDetailsUseCase $getPersonDetailsUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface              $translator
     */
    public function __construct(
        private GetPersonDetailsUseCase $getPersonDetailsUseCase,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Get detailed information about a specific person
     */
    #[
        Route(
            name: "api_person_details",
            defaults: [
                "_api_resource_class" => PersonDetailsEntryPoint::class,
                "_api_operation_name" => 'get_movie_person_details',
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(int $personId, Request $request): JsonResponse
    {
        if ($personId <= 0) {
            throw UserValidationException::invalidFieldValue(
                "person_id",
                $personId,
                "positive integer",
            );
        }

        try {
            $language = $request->query->get("language");
            $region = $request->query->get("region");

            $detailsRequest = new GetPersonDetailsRequest(
                personId: $personId,
                language: $language,
                region: $region,
            );

            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->getPersonDetailsUseCase->execute(
                $detailsRequest,
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
                        "An error occurred while fetching person details",
                    ),
                    "message" => $e->getMessage(),
                    "person_id" => $personId,
                ],
                500,
            );
        }
    }
}