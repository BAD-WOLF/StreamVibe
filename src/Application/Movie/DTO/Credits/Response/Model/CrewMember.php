<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Credits\Response\Model;

/**
 * Uma pessoa da equipe técnica em `data.crew`
 * (GET /movies/{movieId}/credits).
 *
 * NOTA (out/2026): mesmas mudanças do CastMember — o #[NotExposed] e os
 * #[ApiProperty] vazios saíram (sem efeito fora de
 * api_platform.mapping.paths, e prendiam o Application ao API Platform) e
 * as propriedades viraram promovidas no construtor, em
 * `public private(set)`.
 */
final class CrewMember {
    /**
     * @param bool        $adult
     * @param int         $gender               0 não informado, 1 feminino, 2 masculino, 3 não binário
     * @param int         $id                   id da pessoa no TMDB
     * @param string      $known_for_department
     * @param string      $name
     * @param string      $original_name
     * @param float       $popularity
     * @param string|null $profile_path
     * @param string      $credit_id
     * @param string      $department
     * @param string      $job
     */
    public function __construct(
        public private(set) bool $adult,
        public private(set) int $gender,
        public private(set) int $id,
        public private(set) string $known_for_department,
        public private(set) string $name,
        public private(set) string $original_name,
        public private(set) float $popularity,
        public private(set) ?string $profile_path,
        public private(set) string $credit_id,
        public private(set) string $department,
        public private(set) string $job,
    ) {
    }
}
