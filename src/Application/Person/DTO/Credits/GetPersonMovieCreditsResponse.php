<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Credits;

/**
 *
 */
final class GetPersonMovieCreditsResponse {
    /**
     * @param bool        $success
     * @param array|null  $cast
     * @param array|null  $crew
     * @param string|null $message
     * @param array       $errors
     */
    public function __construct(
        public private(set) bool $success {
            /**
             * @return bool
             */ get => $this->success;
        },
        public private(set) ?array $cast = null {
            /**
             * @return array|null
             */ get => $this->cast;
        },
        public private(set) ?array $crew = null {
            /**
             * @return array|null
             */ get => $this->crew;
        },
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
        public private(set) array $errors = [] {
            /**
             * @return array
             */ get => $this->errors;
        },
    ) {
    }

    /**
     * @param array       $cast
     * @param array       $crew
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        array $cast,
        array $crew,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            cast: $cast,
            crew: $crew,
            message: $message,
        );
    }

    /**
     * @param string $message
     * @param array  $errors
     *
     * @return self
     */
    public static function failure(string $message, array $errors = []): self {
        return new self(success: false, message: $message, errors: $errors);
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool {
        return $this->success;
    }

    /**
     * @return array
     */
    public function getCast(): array {
        return $this->cast ?? [];
    }

    /**
     * @return array
     */
    public function getCrew(): array {
        return $this->crew ?? [];
    }

    /**
     * @return int
     */
    public function getTotalCredits(): int {
        return count($this->getCast()) + count($this->getCrew());
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * NOTA (bug corrigido, ago/2026): antes, 'data' era sempre incluída
     * no array, valendo null quando success=false. GenericOutputMapper
     * (Strategy 3, extractValueFromDto) resolve o parâmetro $data de
     * PersonMovieCreditsOutput via `$arrayData['data'] ?? $arrayData` —
     * e isset($arrayData['data']) devolve false quando o valor é null,
     * mesmo a chave existindo. Isso fazia o `??` cair pro lado direito
     * e aninhar o ENVELOPE INTEIRO (success/message/errors) dentro do
     * campo data, em qualquer resposta de falha deste endpoint
     * (validação, erro do TMDB, rate limit, etc.) — payload deformado,
     * embora sem TypeError. Agora 'data' só é incluída no array quando
     * success=true (chave omitida em vez de anulada), igual ao padrão
     * já usado em GetPersonDetailsResponse::asArray() e no resto dos
     * DTOs do projeto — com a chave ausente, o `??` cai corretamente
     * pro envelope plano (success/message/errors) sem duplicação.
     *
     * @return array
     */
    public function toArray(): array {
        $data = [
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
        ];

        if ($this->success) {
            $data['data'] = [
                'cast' => $this->getCast(),
                'crew' => $this->getCrew(),
                'total_credits' => $this->getTotalCredits(),
            ];
        }

        return $data;
    }
}