<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO\Images\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Response DTO for movie images operations
 *
 * This DTO represents the response from getting movie images (posters, backdrops, logos).
 * It is automatically mapped to the GetMovieImagesOutput ApiPlatform
 * Output Model via the auto-mapping system.
 *
 * NOTA (out/2026): as propriedades públicas (fora as #[Ignore]) são o
 * `data` da resposta: movie_id, backdrops, posters e logos. Saiu o
 * `images`, que era um array com as MESMAS três listas (cada imagem
 * aparecia duas vezes no JSON). Saíram também success/errors (envelope que
 * nunca era preenchido aqui), error(), os getters de compatibilidade e o
 * toArray(): falha vira exceção no UseCase, e o JSON sai do
 * GetMovieImagesOutput. Cada imagem é um MovieImage (antes um array com as
 * mesmas chaves).
 */
final class GetMovieImagesResponse
{
    /**
     * @param int                                                               $movieId
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $backdrops
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $posters
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $logos
     * @param string|null                                                       $message
     */
    public function __construct(
        #[SerializedName('movie_id')]
        public private(set) int $movieId {
            /**
             * @return int
             */ get => $this->movieId;
        },
        public private(set) array $backdrops {
            /**
             * @return list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage>
             */ get => $this->backdrops;
        },
        public private(set) array $posters {
            /**
             * @return list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage>
             */ get => $this->posters;
        },
        public private(set) array $logos {
            /**
             * @return list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage>
             */ get => $this->logos;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {}

    /**
     * Create a successful response
     *
     * @param int                                                               $movieId
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $backdrops
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $posters
     * @param list<\App\Application\Movie\DTO\Images\Response\Model\MovieImage> $logos
     * @param string|null                                                       $message
     *
     * @return self
     */
    public static function success(
        int $movieId,
        array $backdrops = [],
        array $posters = [],
        array $logos = [],
        ?string $message = null,
    ): self {
        return new self(
            movieId: $movieId,
            backdrops: $backdrops,
            posters: $posters,
            logos: $logos,
            message: $message,
        );
    }
}
