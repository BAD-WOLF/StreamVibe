<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO\Videos\Response;

use App\Application\Movie\DTO\Videos\Response\Model\MovieVideo;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Response DTO for movie videos operations
 *
 * This DTO represents the response from getting movie videos (trailers, teasers, etc.).
 * It is automatically mapped to the GetMovieVideosOutput ApiPlatform
 * Output Model via the auto-mapping system.
 *
 * NOTA (out/2026): as propriedades públicas (fora as #[Ignore]) são o
 * `data` da resposta: movie_id, videos, trailers, teasers e clips. Saiu o
 * `results`, que era a MESMA lista de `videos` (cada vídeo aparecia duas
 * vezes no JSON). Saíram também success/errors (envelope nunca preenchido
 * aqui), error(), os getters de compatibilidade e o toArray(). Cada vídeo
 * é um MovieVideo (antes um array com as mesmas chaves).
 */
final class GetMovieVideosResponse
{
    /**
     * @param int                                                               $movieId
     * @param list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo> $videos
     * @param string|null                                                       $message
     */
    public function __construct(
        #[SerializedName('movie_id')]
        public private(set) int $movieId {
            /**
             * @return int
             */ get => $this->movieId;
        },
        public private(set) array $videos {
            /**
             * @return list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo>
             */ get => $this->videos;
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
     * @param list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo> $videos
     * @param string|null                                                       $message
     *
     * @return self
     */
    public static function success(
        int $movieId,
        array $videos,
        ?string $message = null,
    ): self {
        return new self(
            movieId: $movieId,
            videos: $videos,
            message: $message,
        );
    }

    /* ============================
     *  Virtual / Computed Properties
     *  (ago/2026 — mesma lógica de filtragem que já existia em
     *  GetMovieVideosController, movida pra cá pra que o Output
     *  consiga mapear trailers/teasers/clips via acesso direto de
     *  propriedade, sem precisar de fieldMap)
     * ============================ */

    /*
     * NOTA: trailers/teasers/clips ficam SEM #[Ignore] de propósito — são o
     * objetivo do endpoint, não vazamento de detalhe interno. Eles saem
     * só dentro de `data`: o Output não os repete no nível dele.
     *
     * NOTA (out/2026): o tipo dos itens vai em @var. Com @return (como
     * estava) o PropertyInfo não lê o docblock de uma propriedade, ficava
     * só com o "array" do PHP, e o Swagger documentava as três listas como
     * listas de string — o JSON real sempre trouxe objetos MovieVideo.
     */

    /**
     * @var list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo>
     */
    public array $trailers {
        get => $this->filterVideosByType('trailer');
    }

    /**
     * @var list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo>
     */
    public array $teasers {
        get => $this->filterVideosByType('teaser');
    }

    /**
     * @var list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo>
     */
    public array $clips {
        get => $this->filterVideosByType('clip');
    }

    /**
     * Filtra $videos por tipo (case-insensitive), reaproveitado pelos
     * três property hooks acima.
     *
     * @param string $type
     *
     * @return list<\App\Application\Movie\DTO\Videos\Response\Model\MovieVideo>
     */
    private function filterVideosByType(string $type): array
    {
        return array_values(
            array_filter(
                $this->videos,
                static fn(MovieVideo $video): bool => strtolower($video->type) === $type,
            ),
        );
    }
}
