<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Details\Schema;

use ArrayObject;

/**
 *
 */
final class MovieNotFoundSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Movie not found with the provided ID'
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
                        'search_attempted' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                    'example' => 999999,
                                ],
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
                                'Check if the movie ID is correct',
                                'Try searching for the movie by title',
                                'Verify the movie exists in TMDB database',
                            ],
                        ],
                    ],
                    'required' => ['error', 'message', 'movie_id'],
                ],
            ],
        ]);
    }
}
