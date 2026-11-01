<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response;

use App\Application\Movie\DTO\Details\Response\Model\BelongsToCollection;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetMovieDetailsUseCase: o `data` de GET /movies/{movieId}.
 *
 * NOTA (ago/2026): credits/videos/images/recommendations/similar e
 * todos os campos/métodos associados foram removidos — cada um já tem
 * endpoint dedicado (GetMovieCreditsUseCase, etc.). Ver NOTA em
 * GetMovieDetailsUseCase para o motivo completo.
 *
 * NOTA (out/2026): com o auto-mapping ligado, as propriedades públicas
 * abaixo (fora as #[Ignore]) são exatamente o `data` da resposta. Antes o
 * JSON real vinha do toArray() — sem envelope success/data/message e com
 * um conjunto de campos diferente do que o Swagger mostrava. Mudanças:
 * - poster_url, backdrop_url, thumbnail_url, release_year,
 *   rating_percentage e formatted_runtime viraram propriedades de verdade,
 *   preenchidas pelo UseCase (o UseCase já calculava as URLs, mas
 *   success() as descartava). release_year deixou de virar 1970 quando o
 *   TMDB manda release_date vazio.
 * - genres, belongs_to_collection, production_companies,
 *   production_countries e spoken_languages passaram a ser objetos das
 *   classes em ./Model (Genre, BelongsToCollection...), montados pelo
 *   UseCase. Antes essas classes existiam mas nunca eram instanciadas ("schema
 *   aspiracional") e o campo saía como o array cru do TMDB — mesmo JSON,
 *   agora com tipo e documentado no Swagger. OriginCountry saiu: no TMDB
 *   `origin_country` é uma lista de códigos ("US"), não de objetos.
 * - genre_names saiu: repetia o nome de cada item de `genres`.
 * - As propriedades virtuais internas (isAdult, hasPoster, hasTagline...),
 *   asArray/toArray, isSuccess(), getMessage(), getErrors() e o `errors`
 *   (sempre vazio) saíram — ninguém os usava, e o asArray era o formato
 *   antigo, divergente do contrato.
 * - Os #[ApiProperty] vazios saíram: não mudavam nada no Swagger e
 *   prendiam o Application ao API Platform.
 */
final class GetMovieDetailsResponse {
    /**
     * @param bool                                                                       $adult
     * @param string|null                                                                $backdrop_path
     * @param \App\Application\Movie\DTO\Details\Response\Model\BelongsToCollection|null $belongs_to_collection
     * @param int                                                                        $budget
     * @param list<\App\Application\Movie\DTO\Details\Response\Model\Genre>              $genres
     * @param string|null                                                                $homepage
     * @param int                                                                        $id
     * @param string|null                                                                $imdb_id
     * @param list<string>                                                               $origin_country        códigos ISO 3166-1
     * @param string                                                                     $original_language
     * @param string                                                                     $original_title
     * @param string                                                                     $overview
     * @param float                                                                      $popularity
     * @param string|null                                                                $poster_path
     * @param list<\App\Application\Movie\DTO\Details\Response\Model\ProductionCompany>  $production_companies
     * @param list<\App\Application\Movie\DTO\Details\Response\Model\ProductionCountry>  $production_countries
     * @param string                                                                     $release_date          "YYYY-MM-DD" (o TMDB pode mandar vazio)
     * @param int                                                                        $revenue
     * @param int|null                                                                   $runtime               minutos
     * @param list<\App\Application\Movie\DTO\Details\Response\Model\SpokenLanguage>     $spoken_languages
     * @param string                                                                     $status
     * @param string|null                                                                $tagline
     * @param string                                                                     $title
     * @param bool                                                                       $video
     * @param float                                                                      $vote_average          0 a 10
     * @param int                                                                        $vote_count
     * @param string|null                                                                $poster_url            poster em w500
     * @param string|null                                                                $backdrop_url          backdrop em w1280
     * @param string|null                                                                $thumbnail_url         poster em w342
     * @param int|null                                                                   $release_year
     * @param int                                                                        $rating_percentage     vote_average em 0 a 100
     * @param string|null                                                                $formatted_runtime     "2h 16m", "2h" ou "45m"
     * @param string|null                                                                $message
     */
    public function __construct(
        public private(set) bool $adult = false {
            /** @return bool */
            get => $this->adult;
        },

        public private(set) ?string $backdrop_path = null {
            /** @return string|null */
            get => $this->backdrop_path;
        },

        public private(set) ?BelongsToCollection $belongs_to_collection = null {
            /** @return \App\Application\Movie\DTO\Details\Response\Model\BelongsToCollection|null */
            get => $this->belongs_to_collection;
        },

        public private(set) int $budget = 0 {
            /** @return int */
            get => $this->budget;
        },

        public private(set) array $genres = [] {
            /** @return list<\App\Application\Movie\DTO\Details\Response\Model\Genre> */
            get => $this->genres;
        },

        public private(set) ?string $homepage = null {
            /** @return string|null */
            get => $this->homepage;
        },

        public private(set) int $id = 0 {
            /** @return int */
            get => $this->id;
        },

        public private(set) ?string $imdb_id = null {
            /** @return string|null */
            get => $this->imdb_id;
        },

        public private(set) array $origin_country = [] {
            /** @return list<string> */
            get => $this->origin_country;
        },

        public private(set) string $original_language = '' {
            /** @return string */
            get => $this->original_language;
        },

        public private(set) string $original_title = '' {
            /** @return string */
            get => $this->original_title;
        },

        public private(set) string $overview = '' {
            /** @return string */
            get => $this->overview;
        },

        public private(set) float $popularity = 0.0 {
            /** @return float */
            get => $this->popularity;
        },

        public private(set) ?string $poster_path = null {
            /** @return string|null */
            get => $this->poster_path;
        },

        public private(set) array $production_companies = [] {
            /** @return list<\App\Application\Movie\DTO\Details\Response\Model\ProductionCompany> */
            get => $this->production_companies;
        },

        public private(set) array $production_countries = [] {
            /** @return list<\App\Application\Movie\DTO\Details\Response\Model\ProductionCountry> */
            get => $this->production_countries;
        },

        public private(set) string $release_date = '' {
            /** @return string */
            get => $this->release_date;
        },

        public private(set) int $revenue = 0 {
            /** @return int */
            get => $this->revenue;
        },

        public private(set) ?int $runtime = null {
            /** @return int|null */
            get => $this->runtime;
        },

        public private(set) array $spoken_languages = [] {
            /** @return list<\App\Application\Movie\DTO\Details\Response\Model\SpokenLanguage> */
            get => $this->spoken_languages;
        },

        public private(set) string $status = '' {
            /** @return string */
            get => $this->status;
        },

        public private(set) ?string $tagline = null {
            /** @return string|null */
            get => $this->tagline;
        },

        public private(set) string $title = '' {
            /** @return string */
            get => $this->title;
        },

        public private(set) bool $video = false {
            /** @return bool */
            get => $this->video;
        },

        public private(set) float $vote_average = 0.0 {
            /** @return float */
            get => $this->vote_average;
        },

        public private(set) int $vote_count = 0 {
            /** @return int */
            get => $this->vote_count;
        },

        public private(set) ?string $poster_url = null {
            /** @return string|null */
            get => $this->poster_url;
        },

        public private(set) ?string $backdrop_url = null {
            /** @return string|null */
            get => $this->backdrop_url;
        },

        public private(set) ?string $thumbnail_url = null {
            /** @return string|null */
            get => $this->thumbnail_url;
        },

        public private(set) ?int $release_year = null {
            /** @return int|null */
            get => $this->release_year;
        },

        public private(set) int $rating_percentage = 0 {
            /** @return int */
            get => $this->rating_percentage;
        },

        public private(set) ?string $formatted_runtime = null {
            /** @return string|null */
            get => $this->formatted_runtime;
        },

        // NOTA: message é o "envelope" da resposta — o
        // GetMovieDetailsOutput já expõe `message` no nível dele, então
        // #[Ignore] evita que ele apareça repetido dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /** @return string|null */
            get => $this->message;
        },
    ) {
    }

    /**
     * NOTA (correção do bug original): antes, success() tinha 7
     * parâmetros posicionais genéricos ($movieDetails, $credits, ...)
     * repassados posicionalmente pro construtor — que tem vários
     * parâmetros nomeados, todos diferentes. Isso fazia $movieDetails
     * (um array) cair na posição de $adult (que exige bool), gerando
     * TypeError. Agora cada campo do array $movieDetails é extraído e
     * mapeado explicitamente pro parâmetro nomeado correspondente do
     * construtor. ($credits, $videos, $images, $recommendations e
     * $similar saíram depois — ver NOTA no topo da classe.)
     *
     * @param array<string, mixed> $movieDetails campos do TMDB já processados pelo UseCase
     *                                           (itens de ./Model + campos derivados)
     * @param string|null          $message
     *
     * @return self
     */
    public static function success(
        array $movieDetails,
        ?string $message = null,
    ): self {
        return new self(
            adult: (bool) ($movieDetails['adult'] ?? false),
            backdrop_path: $movieDetails['backdrop_path'] ?? null,
            belongs_to_collection: $movieDetails['belongs_to_collection'] ?? null,
            budget: (int) ($movieDetails['budget'] ?? 0),
            genres: $movieDetails['genres'] ?? [],
            homepage: $movieDetails['homepage'] ?? null,
            id: (int) ($movieDetails['id'] ?? 0),
            imdb_id: $movieDetails['imdb_id'] ?? null,
            origin_country: $movieDetails['origin_country'] ?? [],
            original_language: (string) ($movieDetails['original_language'] ?? ''),
            original_title: (string) ($movieDetails['original_title'] ?? ''),
            overview: (string) ($movieDetails['overview'] ?? ''),
            popularity: (float) ($movieDetails['popularity'] ?? 0.0),
            poster_path: $movieDetails['poster_path'] ?? null,
            production_companies: $movieDetails['production_companies'] ?? [],
            production_countries: $movieDetails['production_countries'] ?? [],
            release_date: (string) ($movieDetails['release_date'] ?? ''),
            revenue: (int) ($movieDetails['revenue'] ?? 0),
            runtime: isset($movieDetails['runtime']) ? (int) $movieDetails['runtime'] : null,
            spoken_languages: $movieDetails['spoken_languages'] ?? [],
            status: (string) ($movieDetails['status'] ?? ''),
            tagline: $movieDetails['tagline'] ?? null,
            title: (string) ($movieDetails['title'] ?? ''),
            video: (bool) ($movieDetails['video'] ?? false),
            vote_average: (float) ($movieDetails['vote_average'] ?? 0.0),
            vote_count: (int) ($movieDetails['vote_count'] ?? 0),
            poster_url: $movieDetails['poster_url'] ?? null,
            backdrop_url: $movieDetails['backdrop_url'] ?? null,
            thumbnail_url: $movieDetails['thumbnail_url'] ?? null,
            release_year: $movieDetails['release_year'] ?? null,
            rating_percentage: (int) ($movieDetails['rating_percentage'] ?? 0),
            formatted_runtime: $movieDetails['formatted_runtime'] ?? null,
            message: $message,
        );
    }
}
