<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Details\Model;

use App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "adult": false,
 *         "backdrop_path": "/hZkgoQYus5vegHoetLkCJzb17zJ.jpg",
 *         "belongs_to_collection": null,
 *         "budget": 63000000,
 *         "genres": [{"id": 18, "name": "Drama"}],
 *         "id": 550,
 *         "title": "Fight Club",
 *         ...
 *         "poster_url": "https://image.tmdb.org/t/p/w500/...",
 *         "backdrop_url": "https://image.tmdb.org/t/p/w1280/...",
 *         "thumbnail_url": "https://image.tmdb.org/t/p/w342/...",
 *         "release_year": 1999,
 *         "rating_percentage": 84,
 *         "formatted_runtime": "2h 19m"
 *       },
 *       "message": "Movie details retrieved successfully"
 *     }
 *
 * Mapeado do GetMovieDetailsResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetMovieDetailsResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Details/Model/MovieDetailsOutput. Até aqui
 * só alimentava o Swagger — o JSON real saía do toArray() do DTO (sem
 * envelope e com outros campos), porque o registry nunca chegava ao
 * controller. Renomeado para espelhar o DTO/UseCase/Controller
 * (GetMovieDetails*); `success` virou fixo (true) e fromArray() saiu
 * (ninguém chamava, e passaria um array onde o construtor exige o DTO).
 */
#[AutoMapFromDto(GetMovieDetailsResponse::class)]
final readonly class GetMovieDetailsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Details\Response\GetMovieDetailsResponse $data
     * @param string|null                                                        $message
     */
    public function __construct(
        public GetMovieDetailsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
