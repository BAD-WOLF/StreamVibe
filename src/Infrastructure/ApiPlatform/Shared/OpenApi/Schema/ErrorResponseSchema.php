<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 * Corpo de erro documentado no Swagger: o formato que o ExceptionListener
 * devolve de fato para toda exceção da API.
 *
 *     {
 *       "success": false,
 *       "error": {
 *         "type": "user_validation_error",
 *         "message": "Multiple validation errors occurred",
 *         "errors": ["Movie_id: Movie ID must be between 1 and 999999999"]
 *       }
 *     }
 *
 * `errors` só existe nos erros de validação e `action_required` só nos
 * casos que pedem uma ação do usuário (ex.: email_verification).
 *
 * NOTA (out/2026): substitui as ~20 classes de schema de erro que existiam
 * (BadRequestSchema, MovieApiErrorSchema, ConflictSchema,
 * InvalidTokenSchema...). Elas documentavam formatos que a API nunca
 * devolveu (ex.: {"error": "..."} solto, ou error_details/retry_info/help
 * no erro de filme), então o Swagger mentia sobre os erros. Esta classe é
 * a única fonte, parametrizada pelo tipo e pela mensagem de exemplo.
 */
final class ErrorResponseSchema extends ArrayObject {
    /**
     * @param string        $type           valor de error.type (ex.: "business_logic_error")
     * @param string        $message        exemplo de error.message
     * @param string[]|null $errors         exemplo de error.errors (null = campo ausente)
     * @param string|null   $actionRequired valor de error.action_required (null = campo ausente)
     */
    public function __construct(
        string $type = 'internal_error',
        string $message = 'An internal error occurred. Please try again later.',
        ?array $errors = null,
        ?string $actionRequired = null,
    ) {
        $errorProperties = [
            'type' => [
                'type' => 'string',
                'example' => $type,
            ],
            'message' => [
                'type' => 'string',
                'example' => $message,
            ],
        ];
        $required = ['type', 'message'];

        if ($errors !== null) {
            $errorProperties['errors'] = [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'example' => $errors,
            ];
            $required[] = 'errors';
        }

        if ($actionRequired !== null) {
            $errorProperties['action_required'] = [
                'type' => 'string',
                'example' => $actionRequired,
            ];
            $required[] = 'action_required';
        }

        parent::__construct([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [
                            'type' => 'boolean',
                            'example' => false,
                        ],
                        'error' => [
                            'type' => 'object',
                            'properties' => $errorProperties,
                            'required' => $required,
                        ],
                    ],
                    'required' => ['success', 'error'],
                ],
            ],
        ]);
    }
}
