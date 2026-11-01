<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Details\Model;

use App\Application\Person\DTO\Details\Response\GetPersonDetailsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/person/{personId}/details:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "id": 287,
 *         "name": "Brad Pitt",
 *         "biography": "...",
 *         "birthday": "1963-12-18",
 *         "deathday": null,
 *         ...
 *         "profile_url": "https://image.tmdb.org/t/p/w185/...",
 *         "age": 62,
 *         "is_alive": true,
 *         "gender_display": "Male",
 *         "birth_year": 1963,
 *         "formatted_birth_date": "December 18, 1963"
 *       },
 *       "message": "Person details retrieved successfully"
 *     }
 *
 * Mapeado do GetPersonDetailsResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetPersonDetailsResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Person/Details/Model/PersonDetailsOutput. O JSON
 * real saía do toArray() do DTO — com a pessoa dentro de
 * `data.person` e `errors: []` —, porque o registry nunca chegava ao
 * controller; agora os campos da pessoa vêm direto em `data`. Renomeado
 * para espelhar o DTO/UseCase/Controller (GetPersonDetails*); `success`
 * virou fixo (true) e fromArray() saiu (ninguém chamava, e passaria um
 * array onde o construtor exige o DTO).
 */
#[AutoMapFromDto(GetPersonDetailsResponse::class)]
final readonly class GetPersonDetailsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Person\DTO\Details\Response\GetPersonDetailsResponse $data
     * @param string|null                                                           $message
     */
    public function __construct(
        public GetPersonDetailsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
