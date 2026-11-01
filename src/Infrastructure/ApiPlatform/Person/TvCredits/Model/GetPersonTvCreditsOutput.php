<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\TvCredits\Model;

use App\Application\Person\DTO\TvCredits\Response\GetPersonTvCreditsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/person/{personId}/tv/credits:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "person_id": 287,
 *         "cast": [{"id": 1399, "name": "...", "character": "Himself", "episode_count": 1, ...}],
 *         "crew": [{"id": 79680, "name": "...", "job": "Executive Producer", "department": "Production", ...}]
 *       },
 *       "message": "Person TV credits retrieved successfully"
 *     }
 *
 * Mapeado do GetPersonTvCreditsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro e
 * `message` vem de GetPersonTvCreditsResponse::$message. É esta classe que
 * o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado com o endpoint de créditos de TV (antes só um
 * placeholder sempre null). Espelha o GetPersonMovieCreditsOutput.
 */
#[AutoMapFromDto(GetPersonTvCreditsResponse::class)]
final readonly class GetPersonTvCreditsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Person\DTO\TvCredits\Response\GetPersonTvCreditsResponse $data
     * @param string|null                                                               $message
     */
    public function __construct(
        public GetPersonTvCreditsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
