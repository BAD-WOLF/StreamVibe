<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Recommendations\Model;

use App\Application\Movie\DTO\Recommendations\Response\GetMovieRecommendationsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}/recommendations:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movie_id": 550,
 *         "movies": [{"id": 680, "title": "Pulp Fiction", ...}],
 *         "current_page": 1,
 *         "total_pages": 2,
 *         "total_results": 40,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Movie recommendations retrieved successfully"
 *     }
 *
 * Mapeado do GetMovieRecommendationsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetMovieRecommendationsResponse::$message. É esta
 * classe que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Recommendations/Model/RecommendationsOutput.
 * Até aqui só alimentava o Swagger — o JSON real saía do toArray() do DTO
 * (com a lista duas vezes, em `recommendations` e `results`, e a
 * paginação aninhada). Agora `data` tem o mesmo formato das outras listas
 * de filmes, mais o `movie_id`. Renomeado para espelhar o
 * DTO/UseCase/Controller (GetMovieRecommendations*); saíram `errors`, os
 * getters de envelope e fromArray(); `success` virou fixo (true).
 */
#[AutoMapFromDto(GetMovieRecommendationsResponse::class)]
final readonly class GetMovieRecommendationsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Recommendations\Response\GetMovieRecommendationsResponse $data
     * @param string|null                                                                        $message
     */
    public function __construct(
        public GetMovieRecommendationsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
