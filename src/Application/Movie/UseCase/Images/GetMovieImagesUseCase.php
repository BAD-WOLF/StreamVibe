<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Images;

use App\Application\Movie\DTO\Images\Request\GetMovieImagesRequest;
use App\Application\Movie\DTO\Images\Response\GetMovieImagesResponse;
use App\Application\Movie\DTO\Images\Response\Model\MovieImage;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para imagens de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente o TMDB
 * (/movie/{id}/images), sem buscar detalhes completos do filme antes.
 *
 * NOTA (out/2026): depende da porta MovieDatabaseGatewayInterface e não
 * tem mais o try/catch das exceções do HttpClient (o adaptador já devolve
 * exceção de domínio). A resposta não repete mais as três listas dentro de
 * `images` (ver NOTA do GetMovieImagesResponse), e cada imagem virou um
 * MovieImage (antes um array com as mesmas chaves).
 */
final readonly class GetMovieImagesUseCase {
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
     * @param \App\Application\Movie\DTO\Images\Request\GetMovieImagesRequest $request
     *
     * @return \App\Application\Movie\DTO\Images\Response\GetMovieImagesResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(GetMovieImagesRequest $request): GetMovieImagesResponse {
        $this->requestValidator->validate(
            $request,
            'movie_images_request',
            ['movie_id' => $request->movieId],
        );

        $images = $this->movieDatabaseGateway->getMovieImages(
            $request->movieId,
            $request->language,
        );

        if (!isset($images['id'])) {
            $this->logger->warning('Movie not found for images lookup', [
                'movie_id' => $request->movieId,
            ]);

            throw BusinessLogicException::notFound('Movie', $request->movieId);
        }

        $backdrops = array_map($this->processImage(...), $images['backdrops'] ?? []);
        $posters = array_map($this->processImage(...), $images['posters'] ?? []);
        $logos = array_map($this->processImage(...), $images['logos'] ?? []);

        $this->logger->info('Movie images retrieved successfully', [
            'movie_id' => $request->movieId,
            'backdrops_count' => count($backdrops),
            'posters_count' => count($posters),
            'logos_count' => count($logos),
        ]);

        return GetMovieImagesResponse::success(
            movieId: $request->movieId,
            backdrops: $backdrops,
            posters: $posters,
            logos: $logos,
            message: $this->translator->trans(
                'Movie images retrieved successfully',
            ),
        );
    }

    /**
     * @param array<string, mixed> $image imagem no formato do TMDB
     *
     * @return \App\Application\Movie\DTO\Images\Response\Model\MovieImage
     */
    private function processImage(array $image): MovieImage {
        $filePath = (string) ($image['file_path'] ?? '');

        return new MovieImage(
            file_path: $filePath,
            width: (int) ($image['width'] ?? 0),
            height: (int) ($image['height'] ?? 0),
            aspect_ratio: (float) ($image['aspect_ratio'] ?? 0),
            vote_average: (float) ($image['vote_average'] ?? 0),
            vote_count: (int) ($image['vote_count'] ?? 0),
            iso_639_1: $image['iso_639_1'] ?? null,
            url_original: $this->imageUrlBuilder->build($filePath, 'original'),
            url_w500: $this->imageUrlBuilder->build($filePath, 'w500'),
            url_w780: $this->imageUrlBuilder->build($filePath, 'w780'),
        );
    }
}
