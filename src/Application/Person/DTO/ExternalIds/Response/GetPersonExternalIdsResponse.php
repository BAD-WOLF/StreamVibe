<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\ExternalIds\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetPersonExternalIdsUseCase: o `data` de
 * GET /person/{personId}/external/ids — os ids da pessoa em outros sites
 * (IMDb, Wikidata, redes sociais), como o TMDB os devolve. Id ausente vem
 * null.
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` da resposta;
 * o GetPersonExternalIdsOutput expõe este objeto inteiro em `data`.
 * Convenção dos DTOs de resposta: campo que vem do TMDB como está usa o
 * nome do TMDB (snake_case) na propriedade; campo nosso (person_id) é
 * camelCase com #[SerializedName].
 *
 * NOTA (out/2026): criado com o endpoint de ids externos, que antes era só
 * um placeholder sempre null no GetPersonDetailsUseCase.
 */
final class GetPersonExternalIdsResponse {
    /**
     * @param int         $personId
     * @param string|null $imdb_id      ex.: "nm0000093"
     * @param string|null $wikidata_id  ex.: "Q35332"
     * @param string|null $facebook_id
     * @param string|null $instagram_id
     * @param string|null $tiktok_id
     * @param string|null $twitter_id
     * @param string|null $youtube_id
     * @param string|null $freebase_mid legado (o Freebase foi desativado)
     * @param string|null $freebase_id  legado (o Freebase foi desativado)
     * @param int|null    $tvrage_id    legado (o TVRage foi desativado)
     * @param string|null $message
     */
    public function __construct(
        #[SerializedName('person_id')]
        public private(set) int $personId {
            /**
             * @return int
             */ get => $this->personId;
        },
        public private(set) ?string $imdb_id = null {
            /**
             * @return string|null
             */ get => $this->imdb_id;
        },
        public private(set) ?string $wikidata_id = null {
            /**
             * @return string|null
             */ get => $this->wikidata_id;
        },
        public private(set) ?string $facebook_id = null {
            /**
             * @return string|null
             */ get => $this->facebook_id;
        },
        public private(set) ?string $instagram_id = null {
            /**
             * @return string|null
             */ get => $this->instagram_id;
        },
        public private(set) ?string $tiktok_id = null {
            /**
             * @return string|null
             */ get => $this->tiktok_id;
        },
        public private(set) ?string $twitter_id = null {
            /**
             * @return string|null
             */ get => $this->twitter_id;
        },
        public private(set) ?string $youtube_id = null {
            /**
             * @return string|null
             */ get => $this->youtube_id;
        },
        public private(set) ?string $freebase_mid = null {
            /**
             * @return string|null
             */ get => $this->freebase_mid;
        },
        public private(set) ?string $freebase_id = null {
            /**
             * @return string|null
             */ get => $this->freebase_id;
        },
        public private(set) ?int $tvrage_id = null {
            /**
             * @return int|null
             */ get => $this->tvrage_id;
        },
        // NOTA: message é envelope — o GetPersonExternalIdsOutput já a
        // expõe no nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int $personId
     * @param array<string, mixed> $externalIds resposta do TMDB (/person/{id}/external_ids)
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        int $personId,
        array $externalIds,
        ?string $message = null,
    ): self {
        return new self(
            personId: $personId,
            imdb_id: self::nullableString($externalIds['imdb_id'] ?? null),
            wikidata_id: self::nullableString($externalIds['wikidata_id'] ?? null),
            facebook_id: self::nullableString($externalIds['facebook_id'] ?? null),
            instagram_id: self::nullableString($externalIds['instagram_id'] ?? null),
            tiktok_id: self::nullableString($externalIds['tiktok_id'] ?? null),
            twitter_id: self::nullableString($externalIds['twitter_id'] ?? null),
            youtube_id: self::nullableString($externalIds['youtube_id'] ?? null),
            freebase_mid: self::nullableString($externalIds['freebase_mid'] ?? null),
            freebase_id: self::nullableString($externalIds['freebase_id'] ?? null),
            tvrage_id: isset($externalIds['tvrage_id']) ? (int) $externalIds['tvrage_id'] : null,
            message: $message,
        );
    }

    /**
     * O TMDB manda "" em alguns ids ausentes (em vez de null); a API
     * devolve sempre null para "não tem".
     *
     * @param mixed $value
     *
     * @return string|null
     */
    private static function nullableString(mixed $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
