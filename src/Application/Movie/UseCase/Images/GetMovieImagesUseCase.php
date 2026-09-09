<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Images;

use App\Application\Movie\DTO\GetMovieImagesResponse;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Endpoint dedicado para imagens de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente
 * TmdbApiService::getMovieImages(), sem buscar detalhes completos do
 * filme antes.
 */
final readonly class GetMovieImagesUseCase {
    /**
     * @param \App\Infrastructure\ExternalServices\TmdbApiService $tmdbApiService
     * @param \Psr\Log\LoggerInterface                            $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private TmdbApiService $tmdbApiService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int $movieId
     *
     * @return \App\Application\Movie\DTO\GetMovieImagesResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(int $movieId): GetMovieImagesResponse {
        try {
            $images = $this->tmdbApiService->getMovieImages($movieId);

            if (!isset($images['id'])) {
                $this->logger->warning('Movie not found for images lookup', [
                    'movie_id' => $movieId,
                ]);

                throw BusinessLogicException::notFound('Movie', $movieId);
            }

            $processed = $this->processImages($images);

            $this->logger->info('Movie images retrieved successfully', [
                'movie_id' => $movieId,
                'backdrops_count' => count($processed['backdrops']),
                'posters_count' => count($processed['posters']),
                'logos_count' => count($processed['logos']),
            ]);

            return GetMovieImagesResponse::success(
                images: $processed,
                movieId: $movieId,
                backdrops: $processed['backdrops'],
                posters: $processed['posters'],
                logos: $processed['logos'],
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching movie images', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching movie images', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::serverError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (BusinessLogicException|ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching movie images', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    'An error occurred while fetching movie images',
                ),
                500,
                $e,
            );
        }
    }

    /**
     * Process images data (movida de GetMovieDetailsUseCase, sem
     * alteração de lógica)
     */
    private function processImages(array $images): array {
        return [
            'backdrops' => array_map(
                [$this, 'processImage'],
                $images['backdrops'] ?? [],
            ),
            'posters' => array_map(
                [$this, 'processImage'],
                $images['posters'] ?? [],
            ),
            'logos' => array_map(
                [$this, 'processImage'],
                $images['logos'] ?? [],
            ),
        ];
    }

    private function processImage(array $image): array {
        return [
            'file_path' => $image['file_path'] ?? null,
            'width' => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
            'aspect_ratio' => $image['aspect_ratio'] ?? null,
            'vote_average' => $image['vote_average'] ?? 0,
            'vote_count' => $image['vote_count'] ?? 0,
            'iso_639_1' => $image['iso_639_1'] ?? null,
            'url_original' => $this->buildImageUrl(
                $image['file_path'] ?? null,
                'original',
            ),
            'url_w500' => $this->buildImageUrl(
                $image['file_path'] ?? null,
                'w500',
            ),
            'url_w780' => $this->buildImageUrl(
                $image['file_path'] ?? null,
                'w780',
            ),
        ];
    }

    private function buildImageUrl(
        ?string $path,
        string $size = 'original',
    ): ?string {
        if (empty($path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$path}";
    }
}
