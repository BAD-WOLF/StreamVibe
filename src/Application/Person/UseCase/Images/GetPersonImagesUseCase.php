<?php

declare(strict_types = 1);

namespace App\Application\Person\UseCase\Images;

use App\Application\Person\DTO\Images\Request\GetPersonImagesRequest;
use App\Application\Person\DTO\Images\Response\GetPersonImagesResponse;
use App\Application\Person\DTO\Images\Response\Model\PersonImage;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Fotos de perfil de uma pessoa, via TMDB /person/{id}/images, com as
 * URLs prontas nos tamanhos de foto de perfil do TMDB (original, w185 e
 * h632).
 *
 * NOTA (out/2026): criado agora. Antes existia só o placeholder
 * includeImages no GetPersonDetailsUseCase, que sempre devolvia null.
 * Espelha o GetMovieImagesUseCase (validação, erros 400/404/502/503 e
 * processImage()).
 */
final readonly class GetPersonImagesUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \App\Application\Shared\TmdbImageUrlBuilder                   $imageUrlBuilder
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Person\DTO\Images\Request\GetPersonImagesRequest $request
     *
     * @return \App\Application\Person\DTO\Images\Response\GetPersonImagesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetPersonImagesRequest $request): GetPersonImagesResponse {
        $this->requestValidator->validate(
            $request,
            'person_images_request',
            ['person_id' => $request->personId],
        );

        $images = $this->movieDatabaseGateway->getPersonImages($request->personId);

        if (!isset($images['id'])) {
            $this->logger->warning('Person not found for images lookup', [
                'person_id' => $request->personId,
            ]);

            throw BusinessLogicException::notFound('Person', $request->personId);
        }

        $profiles = array_map($this->processImage(...), $images['profiles'] ?? []);

        $this->logger->info('Person images retrieved successfully', [
            'person_id' => $request->personId,
            'profiles_count' => count($profiles),
        ]);

        return GetPersonImagesResponse::success(
            personId: (int) $images['id'],
            profiles: $profiles,
            message: $this->translator->trans(
                'Person images retrieved successfully',
            ),
        );
    }

    /**
     * @param array<string, mixed> $image imagem no formato do TMDB
     *
     * @return \App\Application\Person\DTO\Images\Response\Model\PersonImage
     */
    private function processImage(array $image): PersonImage {
        $filePath = (string) ($image['file_path'] ?? '');

        return new PersonImage(
            file_path: $filePath,
            width: (int) ($image['width'] ?? 0),
            height: (int) ($image['height'] ?? 0),
            aspect_ratio: (float) ($image['aspect_ratio'] ?? 0),
            vote_average: (float) ($image['vote_average'] ?? 0),
            vote_count: (int) ($image['vote_count'] ?? 0),
            iso_639_1: $image['iso_639_1'] ?? null,
            url_original: $this->imageUrlBuilder->build($filePath, 'original'),
            url_w185: $this->imageUrlBuilder->build($filePath, 'w185'),
            url_h632: $this->imageUrlBuilder->build($filePath, 'h632'),
        );
    }
}
