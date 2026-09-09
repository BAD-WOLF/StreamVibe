<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 *
 */
final class NotFoundSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Resource not found'
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
                    ],
                    'required' => ['error'],
                ],
            ],
        ]);
    }
}
