<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Videos;

use App\Application\Movie\DTO\GetMovieVideosResponse;
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
 * Endpoint dedicado para vídeos de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente
 * TmdbApiService::getMovieVideos(), sem buscar detalhes completos do
 * filme antes. Não precisa filtrar trailers/teasers/clips aqui —
 * GetMovieVideosResponse já faz isso via property hooks
 * ($trailers/$teasers/$clips), lendo de $videos.
 */
final readonly class GetMovieVideosUseCase {
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
     * @return \App\Application\Movie\DTO\GetMovieVideosResponse
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(int $movieId): GetMovieVideosResponse {
        try {
            $response = $this->tmdbApiService->getMovieVideos($movieId);

            if (!isset($response['id'])) {
                $this->logger->warning('Movie not found for videos lookup', [
                    'movie_id' => $movieId,
                ]);

                throw BusinessLogicException::notFound('Movie', $movieId);
            }

            $processed = $this->processVideos($response['results'] ?? []);

            $this->logger->info('Movie videos retrieved successfully', [
                'movie_id' => $movieId,
                'videos_count' => count($processed),
            ]);

            return GetMovieVideosResponse::success(
                videos: $processed,
                movieId: $movieId,
                results: $processed,
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error fetching movie videos', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
            ]);

            throw ExternalServiceException::clientError(
                'TMDB Movie API',
                $e->getMessage(),
                ['movie_id' => $movieId],
            );
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error fetching movie videos', [
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
            $this->logger->error('Unexpected error fetching movie videos', [
                'movie_id' => $movieId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                $this->translator->trans(
                    'An error occurred while fetching movie videos',
                ),
                500,
                $e,
            );
        }
    }

    /**
     * Process videos data (movida de GetMovieDetailsUseCase, sem
     * alteração de lógica)
     */
    private function processVideos(array $videos): array {
        return array_map(function (array $video) {
            return [
                'id' => $video['id'] ?? null,
                'key' => $video['key'] ?? null,
                'name' => $video['name'] ?? null,
                'site' => $video['site'] ?? null,
                'type' => $video['type'] ?? null,
                'size' => $video['size'] ?? null,
                'official' => $video['official'] ?? false,
                'published_at' => $video['published_at'] ?? null,
                'youtube_url' =>
                    $video['site'] === 'YouTube' && !empty($video['key'])
                        ? "https://www.youtube.com/watch?v={$video['key']}"
                        : null,
                'youtube_embed_url' =>
                    $video['site'] === 'YouTube' && !empty($video['key'])
                        ? "https://www.youtube.com/embed/{$video['key']}"
                        : null,
            ];
        }, $videos);
    }
}
