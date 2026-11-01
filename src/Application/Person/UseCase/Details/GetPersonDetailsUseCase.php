<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\Details;

use App\Application\Person\DTO\Details\Request\GetPersonDetailsRequest;
use App\Application\Person\DTO\Details\Response\GetPersonDetailsResponse;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Detalhes de uma pessoa (ator, diretor...) no TMDB, com campos
 * derivados: URLs da foto, biografia formatada, idade, is_alive, gênero
 * por extenso e datas formatadas.
 *
 * NOTA (ago/2026): busca de movieCredits/tvCredits/images/externalIds
 * removida daqui. Movie credits ganhou endpoint dedicado
 * (GetPersonMovieCreditsUseCase). Os outros três nunca tiveram
 * implementação real (sempre devolviam null).
 *
 * NOTA (out/2026):
 * - tvCredits, images e externalIds viraram endpoints próprios
 *   (GetPersonTvCreditsUseCase, GetPersonImagesUseCase,
 *   GetPersonExternalIdsUseCase).
 * - Depende da porta MovieDatabaseGatewayInterface, não mais do
 *   TmdbApiService (Infrastructure). Validação pelo RequestValidator,
 *   como nos UseCases de filme.
 * - Erros viraram exceções: antes validação, pessoa inexistente e falha
 *   do TMDB voltavam como failure() — "success": false com status 400
 *   para tudo, inclusive TMDB fora do ar. Agora: 400 (validação), 404
 *   (pessoa inexistente: o adaptador traduz o 404 do TMDB), 502/503
 *   (TMDB). O catch (Exception) genérico saiu.
 * - `language` é repassado ao TMDB (antes era aceito e ignorado).
 * - calculateAge()/formatDate() usam DateTimeImmutable; a regra é a mesma.
 */
final readonly class GetPersonDetailsUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \App\Application\Shared\TmdbImageUrlBuilder                   $imageUrlBuilder
     * @param \App\Application\Shared\DateYearExtractor                     $yearExtractor
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private DateYearExtractor $yearExtractor,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Person\DTO\Details\Request\GetPersonDetailsRequest $request
     *
     * @return \App\Application\Person\DTO\Details\Response\GetPersonDetailsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(
        GetPersonDetailsRequest $request,
    ): GetPersonDetailsResponse {
        $this->requestValidator->validate(
            $request,
            'person_details_request',
            ['person_id' => $request->personId],
        );

        $personDetails = $this->movieDatabaseGateway->getPersonDetails(
            $request->personId,
            $request->language,
        );

        if (!isset($personDetails['id'])) {
            $this->logger->warning('Person not found in TMDB API', [
                'person_id' => $request->personId,
                'response_keys' => array_keys($personDetails),
            ]);

            throw BusinessLogicException::notFound('Person', $request->personId);
        }

        $processedDetails = $this->processPersonDetails($personDetails);

        $this->logger->info('Person details retrieved successfully', [
            'person_id' => $request->personId,
            'person_name' => $processedDetails['name'] ?? 'Unknown',
        ]);

        return GetPersonDetailsResponse::success(
            personDetails: $processedDetails,
            message: $this->translator->trans(
                'Person details retrieved successfully',
            ),
        );
    }

    /**
     * Mantém os campos do TMDB e acrescenta os derivados expostos pelo
     * GetPersonDetailsResponse.
     *
     * @param array<string, mixed> $personDetails pessoa no formato do TMDB
     *
     * @return array<string, mixed>
     */
    private function processPersonDetails(array $personDetails): array {
        $profilePath = $personDetails['profile_path'] ?? null;
        $birthday = $personDetails['birthday'] ?? null;
        $deathday = $personDetails['deathday'] ?? null;

        return [
            'profile_url' => $this->imageUrlBuilder->build($profilePath, 'w185'),
            'profile_large_url' => $this->imageUrlBuilder->build($profilePath, 'w500'),
            'profile_original_url' => $this->imageUrlBuilder->build($profilePath, 'original'),
            'formatted_biography' => $this->formatBiography(
                $personDetails['biography'] ?? null,
            ),
            'age' => $this->calculateAge($birthday, $deathday),
            'is_alive' => empty($deathday),
            'gender_display' => $this->getGenderDisplay(
                (int) ($personDetails['gender'] ?? 0),
            ),
            'birth_year' => $this->yearExtractor->extractYear($birthday),
            'death_year' => $this->yearExtractor->extractYear($deathday),
            'formatted_birth_date' => $this->formatDate($birthday),
            'formatted_death_date' => $this->formatDate($deathday),
        ] + $personDetails;
    }

    /**
     * Biografia sem espaços nas pontas, com quebras de linha normalizadas e
     * no máximo uma linha em branco seguida.
     *
     * @param string|null $biography
     *
     * @return string|null
     */
    private function formatBiography(?string $biography): ?string {
        if ($biography === null || trim($biography) === '') {
            return null;
        }

        $biography = str_replace(["\r\n", "\r"], "\n", trim($biography));

        return (string) preg_replace('/\n{3,}/', "\n\n", $biography);
    }

    /**
     * Idade hoje (ou na data da morte).
     *
     * @param string|null $birthday "YYYY-MM-DD"
     * @param string|null $deathday "YYYY-MM-DD"
     *
     * @return int|null
     */
    private function calculateAge(?string $birthday, ?string $deathday): ?int {
        if (empty($birthday)) {
            return null;
        }

        try {
            $birthDate = new DateTimeImmutable($birthday);
            $endDate = empty($deathday)
                ? new DateTimeImmutable()
                : new DateTimeImmutable($deathday);

            return $birthDate->diff($endDate)->y;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param int $gender 0 não informado, 1 feminino, 2 masculino, 3 não binário
     *
     * @return string
     */
    private function getGenderDisplay(int $gender): string {
        return match ($gender) {
            1 => $this->translator->trans('Female'),
            2 => $this->translator->trans('Male'),
            3 => $this->translator->trans('Non-binary'),
            default => $this->translator->trans('Not specified'),
        };
    }

    /**
     * @param string|null $date "YYYY-MM-DD"
     *
     * @return string|null "January 2, 1963" (ou a própria string, se não for uma data válida)
     */
    private function formatDate(?string $date): ?string {
        if (empty($date)) {
            return null;
        }

        try {
            return new DateTimeImmutable($date)->format('F j, Y');
        } catch (Exception) {
            return $date;
        }
    }
}
