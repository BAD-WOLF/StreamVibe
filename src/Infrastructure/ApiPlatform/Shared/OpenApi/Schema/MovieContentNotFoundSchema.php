<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 *
 */
final class MovieContentNotFoundSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     * @param string $contentType
     */
    public function __construct(
        private readonly string $exampleMessage = 'Movie content not found',
        private readonly string $contentType = 'content'
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
                        'movie_id' => [
                            'type' => 'integer',
                            'example' => 999999,
                        ],
                        'content_type' => [
                            'type' => 'string',
                            'example' => $this->contentType,
                        ],
                        'search_details' => [
                            'type' => 'object',
                            'properties' => [
                                'source' => [
                                    'type' => 'string',
                                    'example' => 'TMDB API',
                                ],
                                'attempted_at' => [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                    'example' => '2025-12-04T12:00:00+00:00',
                                ],
                            ],
                        ],
                        'suggestions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'example' => [
                                'Verify the movie exists',
                                'Check if the content is available',
                                'Try again later',
                            ],
                        ],
                    ],
                    'required' => ['error', 'message', 'movie_id', 'content_type'],
                ],
            ],
        ]);
    }
}
