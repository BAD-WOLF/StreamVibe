<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\ExternalIds\Model;

use App\Application\Person\DTO\ExternalIds\Response\GetPersonExternalIdsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/person/{personId}/external/ids:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "person_id": 287,
 *         "imdb_id": "nm0000093",
 *         "wikidata_id": "Q35332",
 *         "facebook_id": null,
 *         "instagram_id": "...",
 *         "tiktok_id": null,
 *         "twitter_id": null,
 *         "youtube_id": null,
 *         "freebase_mid": "/m/0c6qh",
 *         "freebase_id": "/en/brad_pitt",
 *         "tvrage_id": 59436
 *       },
 *       "message": "Person external IDs retrieved successfully"
 *     }
 *
 * Mapeado do GetPersonExternalIdsResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro e
 * `message` vem de GetPersonExternalIdsResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado com o endpoint de ids externos (antes só um
 * placeholder sempre null).
 */
#[AutoMapFromDto(GetPersonExternalIdsResponse::class)]
final readonly class GetPersonExternalIdsOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Person\DTO\ExternalIds\Response\GetPersonExternalIdsResponse $data
     * @param string|null                                                                   $message
     */
    public function __construct(
        public GetPersonExternalIdsResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
