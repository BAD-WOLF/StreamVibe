<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Shared\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Base dos DTOs de resposta de lista paginada de filmes (busca, tendência,
 * populares, mais bem avaliados, em cartaz, similares e recomendações).
 *
 * NOTA (out/2026): cada endpoint tem o próprio DTO (para o auto-mapping
 * ligar DTO -> Output de forma 1:1), mas o "miolo" é o mesmo: a lista em
 * `movies` e a paginação achatada em `data` — o formato que a busca já
 * devolvia e que o MovieSearchApiTest fixa (current_page, total_pages,
 * total_results). Similar/Recommendations, que antes devolviam a mesma
 * lista duas vezes (similar + results) e a paginação aninhada em
 * `pagination`, passaram a usar este formato.
 *
 * As propriedades públicas (fora as #[Ignore]) SÃO o contrato de `data`:
 * o Output do API Platform expõe este objeto inteiro em `data`, e o nome
 * em JSON vem do #[SerializedName]. `message` é envelope (o Output a
 * expõe no nível de cima), por isso fica fora de `data`.
 */
abstract class PaginatedMovieListResponse {
    /**
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     */
    public function __construct(
        public private(set) array $movies {
            /**
             * @return list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem>
             */ get => $this->movies;
        },
        #[SerializedName('current_page')]
        public private(set) int $page {
            /**
             * @return int
             */ get => $this->page;
        },
        #[SerializedName('total_pages')]
        public private(set) int $totalPages {
            /**
             * @return int
             */ get => $this->totalPages;
        },
        #[SerializedName('total_results')]
        public private(set) int $totalResults {
            /**
             * @return int
             */ get => $this->totalResults;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    #[SerializedName('has_next')]
    public bool $hasNext {
        /**
         * @return bool
         */
        get => $this->page < $this->totalPages;
    }

    #[SerializedName('has_previous')]
    public bool $hasPrevious {
        /**
         * @return bool
         */
        get => $this->page > 1;
    }

    #[SerializedName('next_page')]
    public ?int $nextPage {
        /**
         * @return int|null
         */
        get => $this->hasNext ? $this->page + 1 : null;
    }

    #[SerializedName('previous_page')]
    public ?int $previousPage {
        /**
         * @return int|null
         */
        get => $this->hasPrevious ? $this->page - 1 : null;
    }
}
