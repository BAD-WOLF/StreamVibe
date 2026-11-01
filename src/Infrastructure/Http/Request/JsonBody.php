<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Exception\UserValidationException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Corpo JSON de uma requisição, com leitura tipada dos campos:
 *
 *     $body = JsonBody::fromRequest($request);
 *
 *     new RegisterUserRequest(
 *         email: $body->string("email"),
 *         password: $body->string("password"),
 *         agreeTerms: $body->bool("agreeTerms"),
 *     );
 *
 * - corpo vazio, JSON malformado ou que não é um objeto: 400
 *   (UserValidationException::invalidRequestBody());
 * - campo ausente ou null: "" em string() — a constraint NotBlank do
 *   Request DTO responde "... cannot be blank" — e o default em bool();
 * - campo com tipo errado: 400 com o nome do campo, no mesmo formato dos
 *   erros do RequestValidator ("Email: This value should be of type
 *   string.").
 *
 * Os valores não vão para a exceção (nem para o log): o corpo pode ter
 * senha.
 *
 * NOTA (out/2026): criado com os controllers de autenticação. Cada um
 * fazia json_decode() + `$data["campo"] ?? ""` e passava o valor direto
 * para o Request DTO. Com strict_types, um tipo errado no JSON
 * ({"email": 123}, {"agreeTerms": "sim"}, {"email": ["a"]}) virava
 * TypeError no construtor do DTO (ou no trim() do reenvio) — 500 em vez
 * de 400.
 */
final readonly class JsonBody
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private array $data,
    ) {}

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return self
     * @throws \App\Domain\Exception\UserValidationException corpo vazio, JSON malformado ou que não é um objeto
     */
    public static function fromRequest(Request $request): self
    {
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            throw UserValidationException::invalidRequestBody();
        }

        // Um array JSON ([1, 2]) também vira array no PHP; só objeto serve.
        if ($data !== [] && array_is_list($data)) {
            throw UserValidationException::invalidRequestBody();
        }

        return new self($data);
    }

    /**
     * Campo de texto. Ausente ou null vira "" (a validação do DTO decide
     * se o campo é obrigatório).
     *
     * @param string $key
     *
     * @return string
     * @throws \App\Domain\Exception\UserValidationException valor que não é string
     */
    public function string(string $key): string
    {
        $value = $this->data[$key] ?? null;

        if ($value === null) {
            return "";
        }

        if (!is_string($value)) {
            throw $this->invalidType($key, "string");
        }

        return $value;
    }

    /**
     * Campo booleano. Aceita true/false e as formas usuais em texto ou
     * número ("true", "false", "1", "0", "yes", "no", "on", "off", 1, 0).
     *
     * @param string $key
     * @param bool   $default valor quando o campo está ausente ou null
     *
     * @return bool
     * @throws \App\Domain\Exception\UserValidationException valor que não é booleano
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->data[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $boolean = is_scalar($value)
            ? filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : null;

        if ($boolean === null) {
            throw $this->invalidType($key, "bool");
        }

        return $boolean;
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return \App\Domain\Exception\UserValidationException
     */
    private function invalidType(string $key, string $type): UserValidationException
    {
        return UserValidationException::multipleValidationErrors([
            $key => sprintf("This value should be of type %s.", $type),
        ]);
    }
}
