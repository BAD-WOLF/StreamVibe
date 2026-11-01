<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response\Model;

/**
 * A coleção (franquia) do filme em `data.belongs_to_collection`
 * (GET /movies/{movieId}); null quando o filme não faz parte de uma.
 *
 * NOTA (out/2026): a classe existia mas nunca era instanciada (o campo
 * saía como o array cru do TMDB). Agora o GetMovieDetailsUseCase monta o
 * objeto; o JSON é o mesmo. poster_path/backdrop_path viraram ?string: o
 * TMDB manda null para coleção sem imagem, e o tipo string antigo não
 * aceitaria. Propriedades em `public private(set)` no construtor, como os
 * outros Response/Model; o #[ApiProperty] vazio saiu (não tinha efeito e
 * prendia o Application ao API Platform).
 */
final class BelongsToCollection {
    /**
     * @param int         $id
     * @param string      $name
     * @param string|null $poster_path
     * @param string|null $backdrop_path
     */
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) ?string $poster_path,
        public private(set) ?string $backdrop_path,
    ) {
    }
}
