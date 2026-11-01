<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Details\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetPersonDetailsUseCase: o `data` de
 * GET /person/{personId}/details.
 *
 * Campos do TMDB + os derivados que o UseCase calcula (URLs da foto em
 * três tamanhos, biografia formatada, idade, is_alive, gênero por extenso,
 * anos e datas formatadas de nascimento e morte).
 *
 * NOTA (ago/2026): movieCredits/tvCredits/images/externalIds e todos os
 * métodos/propriedades computadas relacionados foram removidos. Movie
 * credits ganhou endpoint dedicado.
 *
 * NOTA (out/2026):
 * - Movido de DTO/Details/ para DTO/Details/Response/, espelhando o
 *   módulo Movie. tvCredits, images e externalIds viraram endpoints
 *   próprios (GetPersonTvCredits, GetPersonImages, GetPersonExternalIds).
 * - As propriedades públicas (fora as #[Ignore]) SÃO o `data` da
 *   resposta, como em GetMovieDetailsResponse: os campos da pessoa vêm
 *   direto em `data`. Antes o JSON real vinha do toArray() —
 *   {"success", "message", "errors": [], "data": {"person": {...}}}, com
 *   a pessoa num nível a mais — e o Swagger mostrava outra coisa.
 * - Saíram success/errors e failure(): validação, pessoa inexistente e
 *   falha do TMDB agora são exceções (400/404/502/503 pelo
 *   ExceptionListener). Antes viravam "success": false com status 400 —
 *   inclusive pessoa inexistente e TMDB fora do ar.
 * - Saíram as propriedades virtuais (personName, personAge, isPersonAlive,
 *   asArray...) e os getters de compatibilidade: repetiam campos que já
 *   estão em `data`.
 */
final class GetPersonDetailsResponse {
    /**
     * @param int          $id
     * @param string       $name
     * @param string       $biography
     * @param string|null  $birthday             "YYYY-MM-DD"
     * @param string|null  $deathday             "YYYY-MM-DD"
     * @param string|null  $place_of_birth
     * @param string|null  $profile_path
     * @param string|null  $known_for_department "Acting", "Directing"...
     * @param float        $popularity
     * @param int          $gender               0 não informado, 1 feminino, 2 masculino, 3 não binário
     * @param bool         $adult
     * @param list<string> $also_known_as
     * @param string|null  $homepage
     * @param string|null  $imdb_id
     * @param string|null  $profile_url          foto em w185
     * @param string|null  $profile_large_url    foto em w500
     * @param string|null  $profile_original_url foto no tamanho original
     * @param string|null  $formatted_biography  biografia sem linhas em branco repetidas
     * @param int|null     $age                  idade hoje, ou ao morrer
     * @param bool         $is_alive
     * @param string       $gender_display       gênero por extenso (traduzido)
     * @param int|null     $birth_year
     * @param int|null     $death_year
     * @param string|null  $formatted_birth_date "January 2, 1963"
     * @param string|null  $formatted_death_date
     * @param string|null  $message
     */
    public function __construct(
        public private(set) int $id = 0 {
            /** @return int */
            get => $this->id;
        },

        public private(set) string $name = '' {
            /** @return string */
            get => $this->name;
        },

        public private(set) string $biography = '' {
            /** @return string */
            get => $this->biography;
        },

        public private(set) ?string $birthday = null {
            /** @return string|null */
            get => $this->birthday;
        },

        public private(set) ?string $deathday = null {
            /** @return string|null */
            get => $this->deathday;
        },

        public private(set) ?string $place_of_birth = null {
            /** @return string|null */
            get => $this->place_of_birth;
        },

        public private(set) ?string $profile_path = null {
            /** @return string|null */
            get => $this->profile_path;
        },

        public private(set) ?string $known_for_department = null {
            /** @return string|null */
            get => $this->known_for_department;
        },

        public private(set) float $popularity = 0.0 {
            /** @return float */
            get => $this->popularity;
        },

        public private(set) int $gender = 0 {
            /** @return int */
            get => $this->gender;
        },

        public private(set) bool $adult = false {
            /** @return bool */
            get => $this->adult;
        },

        public private(set) array $also_known_as = [] {
            /** @return list<string> */
            get => $this->also_known_as;
        },

        public private(set) ?string $homepage = null {
            /** @return string|null */
            get => $this->homepage;
        },

        public private(set) ?string $imdb_id = null {
            /** @return string|null */
            get => $this->imdb_id;
        },

        public private(set) ?string $profile_url = null {
            /** @return string|null */
            get => $this->profile_url;
        },

        public private(set) ?string $profile_large_url = null {
            /** @return string|null */
            get => $this->profile_large_url;
        },

        public private(set) ?string $profile_original_url = null {
            /** @return string|null */
            get => $this->profile_original_url;
        },

        public private(set) ?string $formatted_biography = null {
            /** @return string|null */
            get => $this->formatted_biography;
        },

        public private(set) ?int $age = null {
            /** @return int|null */
            get => $this->age;
        },

        public private(set) bool $is_alive = true {
            /** @return bool */
            get => $this->is_alive;
        },

        public private(set) string $gender_display = '' {
            /** @return string */
            get => $this->gender_display;
        },

        public private(set) ?int $birth_year = null {
            /** @return int|null */
            get => $this->birth_year;
        },

        public private(set) ?int $death_year = null {
            /** @return int|null */
            get => $this->death_year;
        },

        public private(set) ?string $formatted_birth_date = null {
            /** @return string|null */
            get => $this->formatted_birth_date;
        },

        public private(set) ?string $formatted_death_date = null {
            /** @return string|null */
            get => $this->formatted_death_date;
        },

        // NOTA: message é o "envelope" da resposta — o
        // GetPersonDetailsOutput já expõe `message` no nível dele, então
        // #[Ignore] evita que ele apareça repetido dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /** @return string|null */
            get => $this->message;
        },
    ) {
    }

    /**
     * @param array<string, mixed> $personDetails campos do TMDB + os derivados calculados pelo UseCase
     * @param string|null          $message
     *
     * @return self
     */
    public static function success(
        array $personDetails,
        ?string $message = null,
    ): self {
        return new self(
            id: (int) ($personDetails['id'] ?? 0),
            name: (string) ($personDetails['name'] ?? ''),
            biography: (string) ($personDetails['biography'] ?? ''),
            birthday: $personDetails['birthday'] ?? null,
            deathday: $personDetails['deathday'] ?? null,
            place_of_birth: $personDetails['place_of_birth'] ?? null,
            profile_path: $personDetails['profile_path'] ?? null,
            known_for_department: $personDetails['known_for_department'] ?? null,
            popularity: (float) ($personDetails['popularity'] ?? 0.0),
            gender: (int) ($personDetails['gender'] ?? 0),
            adult: (bool) ($personDetails['adult'] ?? false),
            also_known_as: $personDetails['also_known_as'] ?? [],
            homepage: $personDetails['homepage'] ?? null,
            imdb_id: $personDetails['imdb_id'] ?? null,
            profile_url: $personDetails['profile_url'] ?? null,
            profile_large_url: $personDetails['profile_large_url'] ?? null,
            profile_original_url: $personDetails['profile_original_url'] ?? null,
            formatted_biography: $personDetails['formatted_biography'] ?? null,
            age: $personDetails['age'] ?? null,
            is_alive: (bool) ($personDetails['is_alive'] ?? true),
            gender_display: (string) ($personDetails['gender_display'] ?? ''),
            birth_year: $personDetails['birth_year'] ?? null,
            death_year: $personDetails['death_year'] ?? null,
            formatted_birth_date: $personDetails['formatted_birth_date'] ?? null,
            formatted_death_date: $personDetails['formatted_death_date'] ?? null,
            message: $message,
        );
    }
}
