<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema;

use ArrayObject;

/**
 *
 */
final class TooManyRequestsSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Rate limit exceeded for password reset requests'
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
                        'rate_limit' => [
                            'type' => 'object',
                            'properties' => [
                                'max_attempts' => [
                                    'type' => 'integer',
                                    'example' => 5,
                                ],
                                'time_window' => [
                                    'type' => 'string',
                                    'example' => '15 minutes',
                                ],
                                'attempts_used' => [
                                    'type' => 'integer',
                                    'example' => 6,
                                ],
                                'reset_time' => [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                    'example' => '2025-12-02T18:00:00+00:00',
                                ],
                            ],
                        ],
                        'retry_after' => [
                            'type' => 'integer',
                            'example' => 900,
                            'description' => 'Seconds to wait before next attempt',
                        ],
                        'suggestion' => [
                            'type' => 'string',
                            'example' => 'Please wait before making another password reset request',
                        ],
                    ],
                    'required' => ['error', 'message', 'rate_limit', 'retry_after'],
                ],
            ],
        ]);
    }
}
