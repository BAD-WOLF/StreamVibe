<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response\Model;

/**
 * Um gênero em `data.genres` (GET /movies/{movieId}).
 *
 * NOTA (out/2026): era a classe Genres (plural, para um item só), que
 * nunca era instanciada — `genres` saía como o array cru do TMDB e o
 * Swagger só sabia dizer "array". Agora o GetMovieDetailsUseCase monta
 * cada item com esta classe; o JSON é o mesmo ({"id", "name"}).
 * Propriedades em `public private(set)` no construtor, como os outros
 * Response/Model (CastMember, MovieListItem...); o #[ApiProperty] vazio
 * saiu: não tinha efeito e prendia o Application ao API Platform.
 */
final class Genre {
    /**
     * @param int    $id
     * @param string $name
     */
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
    ) {
    }
}
