<?php

declare(strict_types = 1);

namespace App\Application\Movie\Shared;

/**
 * Uma página de lista de filmes já convertida (itens + paginação), pronta
 * para virar o DTO de resposta de cada endpoint de lista.
 *
 * NOTA (out/2026): criado junto com MovieListItemMapper::mapPage(). Antes
 * cada um dos 7 UseCases de lista repetia a checagem de "results" e a
 * leitura de page/total_pages/total_results do payload do TMDB.
 */
final readonly class MovieListPage {
    /**
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                $page
     * @param int                                                                $totalPages
     * @param int                                                                $totalResults
     */
    public function __construct(
        public array $movies,
        public int $page,
        public int $totalPages,
        public int $totalResults,
    ) {
    }
}
