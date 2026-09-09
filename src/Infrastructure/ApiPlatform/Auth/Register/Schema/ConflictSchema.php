<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\Register\Schema;

use ArrayObject;

/**
 *
 */
final class ConflictSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'User already exists'
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
