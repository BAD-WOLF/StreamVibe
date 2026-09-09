<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Ignore;
/**
 * NOTA (ago/2026): credits/videos/images/recommendations/similar e
 * todos os campos/métodos associados foram removidos — cada um já tem
 * endpoint dedicado (GetMovieCreditsUseCase, etc.). Ver NOTA em
 * GetMovieDetailsUseCase para o motivo completo.
 *
 * genres, production_companies, production_countries,
 * spoken_languages e origin_country continuam tipados como array — as
 * classes em ./Model não têm construtor e nunca são de fato
 * instanciadas, permanecem só como schema aspiracional.
 */
final class GetMovieDetailsResponse {
    #[Ignore]
    public bool $isAdult {
        get => $this->adult;
    }
    #[Ignore]
    public bool $hasPoster {
        get => !empty($this->poster_path);
    }
    #[Ignore]
    public bool $hasBackdrop {
        get => !empty($this->backdrop_path);
    }
    #[Ignore]
    public bool $hasCollection {
        get => $this->belongs_to_collection !== null;
    }
    #[Ignore]
    public bool $hasTagline {
        get => !empty($this->tagline);
    }
    #[Ignore]
    public bool $hasHomepage {
        get => !empty($this->homepage);
    }
    #[Ignore]
    public bool $hasRuntime {
        get => $this->runtime !== null && $this->runtime > 0;
    }
    #[Ignore]
    public bool $isReleased {
        get => strtotime($this->release_date) <= time();
    }
    #[Ignore]
    public int $releaseYear {
        get => (int)date('Y', strtotime($this->release_date));
    }
    #[Ignore]
    public int $ratingPercentage {
        get => (int)round($this->vote_average * 10);
    }
    #[Ignore]
    public string $formattedRuntime {
        get {
            if ($this->runtime === null) {
                return '';
            }

            $h = intdiv($this->runtime, 60);
            $m = $this->runtime % 60;

            return match (true) {
                $h > 0 && $m > 0 => "{$h}h {$m}m",
                $h > 0 => "{$h}h",
                default => "{$m}m",
            };
        }
    }

    /**
     * @return array<string>
     */
    #[Ignore]
    public array $genreNames {
        get => array_map(
            static fn(array $genre): string => $genre['name'] ?? '',
            $this->genres,
        );
    }
    #[Ignore]
    public bool $hasValidData {
        get =>
            $this->id > 0 &&
            $this->title !== '' &&
            $this->release_date !== '';
    }
    #[Ignore]
    public array $asArray {
        get => [
            'id' => $this->id,
            'adult' => $this->adult,
            'title' => $this->title,
            'original_title' => $this->original_title,
            'overview' => $this->overview,
            'tagline' => $this->tagline,
            'release_date' => $this->release_date,
            'release_year' => $this->releaseYear,
            'runtime' => $this->runtime,
            'formatted_runtime' => $this->formattedRuntime,
            'status' => $this->status,
            'popularity' => $this->popularity,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'rating_percentage' => $this->ratingPercentage,
            'genres' => $this->genres,
            'genre_names' => $this->genreNames,
            'poster_path' => $this->poster_path,
            'backdrop_path' => $this->backdrop_path,
            'belongs_to_collection' => $this->belongs_to_collection,
            'production_companies' => $this->production_companies,
            'production_countries' => $this->production_countries,
            'spoken_languages' => $this->spoken_languages,
            'homepage' => $this->homepage,
            'imdb_id' => $this->imdb_id,
            'origin_country' => $this->origin_country,
        ];
    }

    public function __construct(
        #[ApiProperty]
        public private(set) bool $adult {
            get => $this->adult;
        },

        #[ApiProperty]
        public private(set) ?string $backdrop_path = null {
            get => $this->backdrop_path;
        },

        #[ApiProperty]
        public private(set) ?array $belongs_to_collection = null {
            get => $this->belongs_to_collection;
        },

        #[ApiProperty]
        public private(set) int $budget = 0 {
            get => $this->budget;
        },

        #[ApiProperty]
        public private(set) array $genres {
            get => $this->genres;
        },

        #[ApiProperty]
        public private(set) ?string $homepage = null {
            get => $this->homepage;
        },

        #[ApiProperty]
        public private(set) int $id {
            get => $this->id;
        },

        #[ApiProperty]
        public private(set) ?string $imdb_id = null {
            get => $this->imdb_id;
        },

        #[ApiProperty]
        public private(set) array $origin_country {
            get => $this->origin_country;
        },

        #[ApiProperty]
        public private(set) string $original_language {
            get => $this->original_language;
        },

        #[ApiProperty]
        public private(set) string $original_title {
            get => $this->original_title;
        },

        #[ApiProperty]
        public private(set) string $overview {
            get => $this->overview;
        },

        #[ApiProperty]
        public private(set) float $popularity {
            get => $this->popularity;
        },

        #[ApiProperty]
        public private(set) ?string $poster_path = null {
            get => $this->poster_path;
        },

        #[ApiProperty]
        public private(set) array $production_companies {
            get => $this->production_companies;
        },

        #[ApiProperty]
        public private(set) array $production_countries {
            get => $this->production_countries;
        },

        #[ApiProperty]
        public private(set) string $release_date {
            get => $this->release_date;
        },

        #[ApiProperty]
        public private(set) int $revenue = 0 {
            get => $this->revenue;
        },

        #[ApiProperty]
        public private(set) ?int $runtime = null {
            get => $this->runtime;
        },

        #[ApiProperty]
        public private(set) array $spoken_languages {
            get => $this->spoken_languages;
        },

        #[ApiProperty]
        public private(set) string $status {
            get => $this->status;
        },

        #[ApiProperty]
        public private(set) ?string $tagline = null {
            get => $this->tagline;
        },

        #[ApiProperty]
        public private(set) string $title {
            get => $this->title;
        },

        #[ApiProperty]
        public private(set) bool $video {
            get => $this->video;
        },

        #[ApiProperty]
        public private(set) float $vote_average {
            get => $this->vote_average;
        },

        #[ApiProperty]
        public private(set) int $vote_count {
            get => $this->vote_count;
        },

        public private(set) ?string $message = null {
            get => $this->message;
        },

        public private(set) array $errors = [] {
            get => $this->errors;
        },
    ) {
    }

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
            runtime: $movieDetails['runtime'] ?? null,
            spoken_languages: $movieDetails['spoken_languages'] ?? [],
            status: (string) ($movieDetails['status'] ?? ''),
            tagline: $movieDetails['tagline'] ?? null,
            title: (string) ($movieDetails['title'] ?? ''),
            video: (bool) ($movieDetails['video'] ?? false),
            vote_average: (float) ($movieDetails['vote_average'] ?? 0.0),
            vote_count: (int) ($movieDetails['vote_count'] ?? 0),
            message: $message,
        );
    }

    #[Ignore]
    public function isSuccess(): bool {
        return true;
    }

    public function getMessage(): ?string {
        return $this->message;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function toArray(): array {
        return $this->asArray;
    }
}
