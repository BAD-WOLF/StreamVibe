<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 *
 */
final class GoneSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Resource has expired'
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
                        'expired' => [
                            'type' => 'boolean',
                            'example' => true,
                        ],
                        'expired_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2025-12-02T17:00:00+00:00',
                        ],
                    ],
                    'required' => ['error', 'expired'],
                ],
            ],
        ]);
    }
}
