<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Images\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetPersonImagesUseCase: o `data` de
 * GET /person/{personId}/images — as fotos de perfil da pessoa.
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` da resposta;
 * o GetPersonImagesOutput expõe este objeto inteiro em `data`.
 *
 * NOTA (out/2026): criado com o endpoint de imagens de pessoa, que antes
 * era só um placeholder sempre null no GetPersonDetailsUseCase. Espelha o
 * GetMovieImagesResponse ({"movie_id", "backdrops", "posters", "logos"});
 * para pessoa o TMDB só tem `profiles`.
 */
final class GetPersonImagesResponse {
    /**
     * @param int                                                                 $personId
     * @param list<\App\Application\Person\DTO\Images\Response\Model\PersonImage> $profiles
     * @param string|null                                                         $message
     */
    public function __construct(
        #[SerializedName('person_id')]
        public private(set) int $personId {
            /**
             * @return int
             */ get => $this->personId;
        },
        public private(set) array $profiles {
            /**
             * @return list<\App\Application\Person\DTO\Images\Response\Model\PersonImage>
             */ get => $this->profiles;
        },
        // NOTA: message é envelope — o GetPersonImagesOutput já a expõe no
        // nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int                                                                 $personId
     * @param list<\App\Application\Person\DTO\Images\Response\Model\PersonImage> $profiles
     * @param string|null                                                         $message
     *
     * @return self
     */
    public static function success(
        int $personId,
        array $profiles,
        ?string $message = null,
    ): self {
        return new self(
            personId: $personId,
            profiles: $profiles,
            message: $message,
        );
    }
}
