<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema;

use ArrayObject;

/**
 *
 */
final class ValidationErrorSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Validation failed'
    ) {
        parent::__construct([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'error' => [
                            'type' => 'string',
                            'example' => $this->exampleMessage,
                        ],
                        'errors' => [
                            'type' => 'object',
                            'properties' => [
                                'email' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                    'example' => [
                                        'Email address is required',
                                        'Invalid email format',
                                    ],
                                ],
                                'general' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                    'example' => [
                                        'Request data is invalid',
                                    ],
                                ],
                            ],
                        ],
                        'validation_summary' => [
                            'type' => 'string',
                            'example' => 'The provided data failed validation checks',
                        ],
                        'field_count' => [
                            'type' => 'integer',
                            'example' => 2,
                        ],
                    ],
                    'required' => ['error', 'errors'],
                ],
            ],
        ]);
    }
}
