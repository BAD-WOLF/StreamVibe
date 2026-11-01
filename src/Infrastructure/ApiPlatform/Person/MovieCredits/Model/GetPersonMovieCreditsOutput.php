<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\MovieCredits\Model;

use App\Application\Person\DTO\MovieCredits\Response\GetPersonMovieCreditsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/person/{personId}/movie/credits:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "person_id": 287,
 *         "cast": [{"id": 550, "title": "Fight Club", "character": "Tyler Durden", ...}],
 *         "crew": [{"id": 1430, "title": "...", "job": "Producer", "department": "Production", ...}]
 *       },
 *       "message": "Person movie credits retrieved successfully"
 *     }
 *
 * Mapeado do GetPersonMovieCreditsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro e
 * `message` vem de GetPersonMovieCreditsResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Person/Credits/Model/PersonMovieCreditsOutput, com
 * `data` tipado como array (o mapper nunca rodava; o JSON real saía do
 * toArray() do DTO). Agora `data` é o próprio DTO, com `person_id`.
 * Renomeado para espelhar o DTO/UseCase/Controller
 * (GetPersonMovieCredits*); saíram fromArray(), isSuccess(), getData(),
 * getMessage() e toArray() (ninguém chamava); `success` virou fixo
 * (true).
 */
#[AutoMapFromDto(GetPersonMovieCreditsResponse::class)]
final readonly class GetPersonMovieCreditsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Person\DTO\MovieCredits\Response\GetPersonMovieCreditsResponse $data
     * @param string|null                                                                     $message
     */
    public function __construct(
        public GetPersonMovieCreditsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
