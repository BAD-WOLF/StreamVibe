<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Videos\Model;

use App\Application\Movie\DTO\Videos\Response\GetMovieVideosResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}/videos:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movie_id": 550,
 *         "videos": [{"id": "...", "key": "BdJKm16Co6M", "site": "YouTube", "type": "Trailer",
 *                     "youtube_url": "https://www.youtube.com/watch?v=BdJKm16Co6M", ...}],
 *         "trailers": [...],
 *         "teasers": [...],
 *         "clips": [...]
 *       },
 *       "message": "Movie videos retrieved successfully"
 *     }
 *
 * trailers/teasers/clips são `videos` filtrado por tipo (propriedades
 * virtuais do DTO).
 *
 * Mapeado do GetMovieVideosResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetMovieVideosResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Videos/Model/VideosOutput. Até aqui só
 * alimentava o Swagger — o JSON real saía do toArray() do DTO, porque o
 * registry nunca chegava ao controller. Renomeado para espelhar o
 * DTO/UseCase/Controller (GetMovieVideos*). Saíram `errors` (sempre vazio
 * no sucesso), os getters de envelope e fromArray(); `success` virou fixo
 * (true).
 */
#[AutoMapFromDto(GetMovieVideosResponse::class)]
final readonly class GetMovieVideosOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Videos\Response\GetMovieVideosResponse $data
     * @param string|null                                                      $message
     */
    public function __construct(
        public GetMovieVideosResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
