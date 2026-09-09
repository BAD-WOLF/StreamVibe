<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\Schema;

use ArrayObject;

/**
 *
 */
final class TokenNotFoundSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Reset token not found'
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
                        'message' => [
                            'type' => 'string',
                            'example' => $this->exampleMessage,
                        ],
                        'search_criteria' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => [
                                    'type' => 'string',
                                    'example' => 'abc123...',
                                ],
                                'attempted_at' => [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                    'example' => '2025-12-02T17:00:00+00:00',
                                ],
                            ],
                        ],
                    ],
                    'required' => ['error', 'message'],
                ],
            ],
        ]);
    }
}
