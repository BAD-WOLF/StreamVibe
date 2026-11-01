<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Similar\Model;

use App\Application\Movie\DTO\Similar\Response\GetMovieSimilarResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}/similar:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movie_id": 550,
 *         "movies": [{"id": 807, "title": "Se7en", ...}],
 *         "current_page": 1,
 *         "total_pages": 1234,
 *         "total_results": 24680,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Similar movies retrieved successfully"
 *     }
 *
 * Mapeado do GetMovieSimilarResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetMovieSimilarResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Similar/Model/SimilarOutput. Até aqui só
 * alimentava o Swagger — o JSON real saía do toArray() do DTO (com a
 * lista duas vezes, em `similar` e `results`, e a paginação aninhada).
 * Agora `data` tem o mesmo formato das outras listas de filmes, mais o
 * `movie_id`. Renomeado para espelhar o DTO/UseCase/Controller
 * (GetMovieSimilar*); saíram `errors`, os getters de envelope e
 * fromArray(); `success` virou fixo (true).
 */
#[AutoMapFromDto(GetMovieSimilarResponse::class)]
final readonly class GetMovieSimilarOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Similar\Response\GetMovieSimilarResponse $data
     * @param string|null                                                        $message
     */
    public function __construct(
        public GetMovieSimilarResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
