<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Credits\Schema;

use ArrayObject;

/**
 *
 */
final class CreditsNotFoundSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'Credits not found for the specified movie'
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
                        'search_details' => [
                            'type' => 'object',
                            'properties' => [
                                'credits_type' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                    'example' => ['cast', 'crew'],
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
                        'possible_reasons' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'example' => [
                                'Movie exists but has no cast/crew information',
                                'Credits data not yet available in database',
                                'Movie ID is valid but credits are private',
                            ],
                        ],
                        'suggestions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'example' => [
                                'Verify the movie exists using /movies/{id} endpoint',
                                'Try again later as credits might be updated',
                                'Check if the movie has alternative sources',
                            ],
                        ],
                    ],
                    'required' => ['error', 'message', 'movie_id'],
                ],
            ],
        ]);
    }
}
