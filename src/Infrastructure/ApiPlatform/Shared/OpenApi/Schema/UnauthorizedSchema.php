<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 * Corpo do 401 das rotas protegidas por JWT. Quem responde é o
 * LexikJWTAuthenticationBundle (antes do controller), por isso o formato
 * é o dele — {"code": 401, "message": "..."} — e não o do
 * ExceptionListener.
 *
 * NOTA (out/2026): criado junto com o ErrorResponseSchema. Até aqui
 * nenhuma operação documentava o 401, embora filmes, pessoas, imagens e
 * reset-password/requests exijam JWT desde o commit aaa2637.
 */
final class UnauthorizedSchema extends ArrayObject {
    /**
     * @param string $message exemplo de mensagem do bundle
     */
    public function __construct(string $message = 'JWT Token not found') {
        parent::__construct([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'integer',
                            'example' => 401,
                        ],
                        'message' => [
                            'type' => 'string',
                            'example' => $message,
                        ],
                    ],
                    'required' => ['code', 'message'],
                ],
            ],
        ]);
    }
}
