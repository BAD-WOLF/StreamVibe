<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\UserRequests\Schema;

use ArrayObject;

/**
 *
 */
final class InvalidUserIdSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Invalid user ID provided'
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
                        'validation_errors' => [
                            'type' => 'object',
                            'properties' => [
                                'userId' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                    'example' => [
                                        'User ID must be a positive integer',
                                        'User ID cannot be zero or negative',
                                    ],
                                ],
                            ],
                        ],
                        'provided_value' => [
                            'type' => 'mixed',
                            'example' => -1,
                        ],
                    ],
                    'required' => ['error', 'message'],
                ],
            ],
        ]);
    }
}
