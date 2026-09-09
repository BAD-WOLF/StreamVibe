<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 *
 */
class BadRequestSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(private readonly string $exampleMessage = 'Invalid JSON data') {
        parent::__construct([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'error' => [
                            'type' => 'string',
                            'example' => $this->exampleMessage,
                        ],
                    ],
                    'required' => ['error'],
                ],
            ],
        ]);
    }
}
