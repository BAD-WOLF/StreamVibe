<?php

declare(strict_types = 1);

namespace App\Application\Movie\UseCase\Videos;

use App\Application\Movie\DTO\Videos\Request\GetMovieVideosRequest;
use App\Application\Movie\DTO\Videos\Response\GetMovieVideosResponse;
use App\Application\Movie\DTO\Videos\Response\Model\MovieVideo;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint dedicado para vídeos de filme — separado de
 * GetMovieDetailsUseCase. Chama diretamente o TMDB
 * (/movie/{id}/videos), sem buscar detalhes completos do filme antes.
 * Não precisa filtrar trailers/teasers/clips aqui — GetMovieVideosResponse
 * já faz isso via property hooks ($trailers/$teasers/$clips), lendo de
 * $videos.
 *
 * NOTA (out/2026): depende da porta MovieDatabaseGatewayInterface e não
 * tem mais o try/catch das exceções do HttpClient. A lista não vai mais
 * duplicada em `results`. youtube_url/youtube_embed_url deixaram de
 * acessar $video['site'] sem `??` (TMDB sem "site" gerava warning).
 */
final readonly class GetMovieVideosUseCase {
    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Movie\DTO\Videos\Request\GetMovieVideosRequest $request
     *
     * @return \App\Application\Movie\DTO\Videos\Response\GetMovieVideosResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\ExternalServiceException
     * @throws \App\Domain\Exception\BusinessLogicException
     */
    public function execute(GetMovieVideosRequest $request): GetMovieVideosResponse {
        $this->requestValidator->validate(
            $request,
            'movie_videos_request',
            ['movie_id' => $request->movieId],
        );

        $response = $this->movieDatabaseGateway->getMovieVideos(
            $request->movieId,
            $request->language,
        );

        if (!isset($response['id'])) {
            $this->logger->warning('Movie not found for videos lookup', [
                'movie_id' => $request->movieId,
            ]);

            throw BusinessLogicException::notFound('Movie', $request->movieId);
        }

        $videos = array_map($this->processVideo(...), $response['results'] ?? []);

        $this->logger->info('Movie videos retrieved successfully', [
            'movie_id' => $request->movieId,
            'videos_count' => count($videos),
        ]);

        return GetMovieVideosResponse::success(
            movieId: $request->movieId,
            videos: $videos,
            message: $this->translator->trans(
                'Movie videos retrieved successfully',
            ),
        );
    }

    /**
     * @param array<string, mixed> $video vídeo no formato do TMDB
     *
     * @return \App\Application\Movie\DTO\Videos\Response\Model\MovieVideo
     */
    private function processVideo(array $video): MovieVideo {
        $key = (string) ($video['key'] ?? '');
        $site = (string) ($video['site'] ?? '');
        $isYoutube = $site === 'YouTube' && $key !== '';

        return new MovieVideo(
            id: (string) ($video['id'] ?? ''),
            key: $key,
            name: (string) ($video['name'] ?? ''),
            site: $site,
            type: (string) ($video['type'] ?? ''),
            size: (int) ($video['size'] ?? 0),
            official: (bool) ($video['official'] ?? false),
            published_at: $video['published_at'] ?? null,
            youtube_url: $isYoutube ? "https://www.youtube.com/watch?v={$key}" : null,
            youtube_embed_url: $isYoutube ? "https://www.youtube.com/embed/{$key}" : null,
        );
    }
}
