<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO;

use Symfony\Component\Serializer\Attribute\Ignore;


/**
 * NOTA IMPORTANTE (achado durante correção de testes, ago/2026):
 * O sistema de auto-mapeamento via #[AutoMapFromDto] + GenericOutputMapper
 * (ver src/Infrastructure/ApiPlatform/Shared/Mapper/) está com uma peça
 * faltando: nada no projeto varre classes com esse atributo e as registra
 * como serviço com a tag "app.output_mapper" que o OutputMapperRegistry
 * espera (config/services.yaml usa `!tagged_iterator "app.output_mapper"`,
 * mas não existe nenhum CompilerPass/factory produzindo esses serviços).
 * Isso faz OutputMapperRegistry::canMap() retornar sempre false, e todo
 * controller de filme cai no fallback de OutputMappingTrait::
 * safeMapToJsonResponse(), que serializa via $dto->toArray() — ou seja,
 * é o array abaixo (asArray) que efetivamente vai pro cliente hoje, não
 * o SearchMoviesOutput. Os campos de paginação foram achatados direto em
 * 'data' (em vez de aninhados em 'data.pagination') para bater com o
 * contrato já estabelecido pelos testes de API (MovieSearchApiTest).
 * Consertar o wiring do GenericOutputMapper é mudança maior, separada,
 * que não foi feita aqui por afetar os 5 endpoints de filme de uma vez.
 */
final class SearchMoviesResponse {
    public function __construct(
        #[Ignore]
        public private(set) bool $success {
            get => $this->success;
        },
        public private(set) array $movies {
            get => $this->movies;
        },
        public private(set) int $page {
            get => $this->page;
        },
        public private(set) int $totalPages {
            get => $this->totalPages;
        },
        public private(set) int $totalResults {
            get => $this->totalResults;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            get => $this->message;
        },
        #[Ignore]
        public private(set) array $errors = [] {
            get => $this->errors;
        },
    ) {
    }

    public static function success(
        array $movies,
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            movies: $movies,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            message: $message,
        );
    }

    public static function failure(string $message, array $errors = []): self {
        return new self(
            success: false,
            movies: [],
            page: 0,
            totalPages: 0,
            totalResults: 0,
            message: $message,
            errors: $errors,
        );
    }

    // Virtual computed properties — todos internos
    #[Ignore]
    public bool $isSuccess {
        get => $this->success;
    }

    #[Ignore]
    public bool $hasErrors {
        get => !empty($this->errors);
    }

    #[Ignore]
    public bool $hasNext {
        get => $this->page < $this->totalPages;
    }

    #[Ignore]
    public bool $hasPrevious {
        get => $this->page > 1;
    }

    #[Ignore]
    public ?int $nextPage {
        get => $this->hasNext ? $this->page + 1 : null;
    }

    #[Ignore]
    public ?int $previousPage {
        get => $this->hasPrevious ? $this->page - 1 : null;
    }

    #[Ignore]
    public array $asArray {
        get {
            return [
                'success' => $this->success,
                'data' => [
                    'movies' => $this->movies,
                    'total_results' => $this->totalResults,
                    'total_pages' => $this->totalPages,
                    'current_page' => $this->page,
                    'has_next' => $this->hasNext,
                    'has_previous' => $this->hasPrevious,
                    'next_page' => $this->nextPage,
                    'previous_page' => $this->previousPage,
                ],
                'message' => $this->message,
                'errors' => $this->errors,
            ];
        }
    }

    // Compatibility methods
    #[Ignore]
    public function isSuccess(): bool {
        return $this->success;
    }

    public function getMovies(): array {
        return $this->movies;
    }

    public function getPage(): int {
        return $this->page;
    }

    public function getTotalPages(): int {
        return $this->totalPages;
    }

    public function getTotalResults(): int {
        return $this->totalResults;
    }

    public function getMessage(): ?string {
        return $this->message;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    #[Ignore]
    public function hasErrors(): bool {
        return $this->hasErrors;
    }

    public function hasNext(): bool {
        return $this->hasNext;
    }

    public function hasPrevious(): bool {
        return $this->hasPrevious;
    }

    public function getNextPage(): ?int {
        return $this->nextPage;
    }

    public function getPreviousPage(): ?int {
        return $this->previousPage;
    }

    public function toArray(): array {
        return $this->asArray;
    }
}
