<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Credits\Model;

use App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}/credits:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movie_id": 550,
 *         "cast": [{"id": 819, "name": "Edward Norton", "character": "The Narrator", "order": 0, ...}],
 *         "crew": [{"id": 7467, "name": "David Fincher", "department": "Directing", "job": "Director", ...}]
 *       },
 *       "message": "Movie credits retrieved successfully"
 *     }
 *
 * Mapeado do GetMovieCreditsResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetMovieCreditsResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Credits/Model/CreditsOutput. Até aqui só
 * alimentava o Swagger — o JSON real saía do toArray() do DTO, porque o
 * registry nunca chegava ao controller. Renomeado para espelhar o
 * DTO/UseCase/Controller (GetMovieCredits*). Saíram `errors` (sempre
 * vazio no sucesso; erro sai do ExceptionListener), os getters
 * isSuccess()/getData()/getMessage()/getErrors() (o Serializer lê as
 * propriedades públicas) e fromArray(); `success` virou fixo (true).
 */
#[AutoMapFromDto(GetMovieCreditsResponse::class)]
final readonly class GetMovieCreditsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse $data
     * @param string|null                                                        $message
     */
    public function __construct(
        public GetMovieCreditsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
