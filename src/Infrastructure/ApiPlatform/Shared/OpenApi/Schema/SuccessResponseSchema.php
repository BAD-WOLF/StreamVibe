<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 * Corpo de sucesso documentado à mão no Swagger, no formato que o Output
 * devolve de fato:
 *
 *     {"success": true, "data": {...}, "message": "..."}
 *
 * Só nas operações DELETE: nelas o API Platform não liga o Output da
 * EntryPoint à resposta de sucesso (documenta só a descrição, como num
 * 204). Nas outras, o corpo documentado vem do próprio `output:`. O
 * OpenApiContractTest compara este schema com a resposta real.
 *
 * NOTA (nov/2026): criado com a administração de contas
 * (DELETE /admin/users/{userId} e DELETE /admin/users/{userId}/sessions).
 */
final class SuccessResponseSchema extends ArrayObject {
    /**
     * @param array<string, array<string, mixed>> $data    as propriedades de `data` (o schema de cada uma)
     * @param string                              $message exemplo de `message`
     */
    public function __construct(array $data, string $message) {
        parent::__construct([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [
                            'type' => 'boolean',
                            'example' => true,
                        ],
                        'data' => [
                            'type' => 'object',
                            'properties' => $data,
                            'required' => array_keys($data),
                        ],
                        'message' => [
                            'type' => 'string',
                            'example' => $message,
                        ],
                    ],
                    'required' => ['success', 'data', 'message'],
                ],
            ],
        ]);
    }
}
