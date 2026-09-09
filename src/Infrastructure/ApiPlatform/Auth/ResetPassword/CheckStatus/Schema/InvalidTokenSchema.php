<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus\Schema;

use ArrayObject;

/**
 *
 */
final class InvalidTokenSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Invalid reset token'
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
                        'error_code' => [
                            'type' => 'string',
                            'example' => 'INVALID_TOKEN',
                        ],
                        'context' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => [
                                    'type' => 'string',
                                    'example' => 'abc123...',
                                ],
                                'reason' => [
                                    'type' => 'string',
                                    'example' => 'Token format is invalid',
                                ],
                            ],
                        ],
                        'original_error' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'example' => 'Token validation failed',
                                ],
                                'class' => [
                                    'type' => 'string',
                                    'example' => 'InvalidArgumentException',
                                ],
                            ],
                        ],
                    ],
                    'required' => ['error', 'error_code'],
                ],
            ],
        ]);
    }
}
