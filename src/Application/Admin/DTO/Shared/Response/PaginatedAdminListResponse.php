<?php

declare(strict_types = 1);

namespace App\Application\Admin\DTO\Shared\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Base das listas paginadas da administração (as contas e o log de
 * auditoria), com a mesma paginação achatada das listas de filmes
 * (PaginatedMovieListResponse): current_page, total_pages, total_results,
 * has_next, has_previous, next_page e previous_page. A lista em si
 * (`users`, `entries`) é declarada em cada subclasse.
 *
 * As propriedades públicas (fora as #[Ignore]) SÃO o contrato de `data`:
 * o Output do API Platform expõe este objeto inteiro em `data`. `message`
 * é envelope (o Output a expõe no nível de cima).
 *
 * NOTA (nov/2026): criada com a administração de contas.
 */
abstract class PaginatedAdminListResponse {
    /**
     * @param int         $page
     * @param int         $totalPages
     * @param int         $totalResults
     * @param string|null $message
     */
    public function __construct(
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

    /**
     * Quantas páginas $totalResults itens ocupam, $perPage por página. Uma
     * lista vazia tem uma página (a primeira, vazia).
     *
     * @param int $totalResults
     * @param int $perPage
     *
     * @return int
     */
    protected static function pagesFor(int $totalResults, int $perPage): int {
        return max(1, intdiv($totalResults + $perPage - 1, $perPage));
    }
}
