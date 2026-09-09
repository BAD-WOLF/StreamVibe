<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Details\Schema;

use ArrayObject;

/**
 *
 */
final class MovieDetailsErrorSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'An error occurred while fetching movie details'
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
                            'example' => 12345,
                        ],
                        'details' => [
                            'type' => 'object',
                            'properties' => [
                                'error_type' => [
                                    'type' => 'string',
                                    'example' => 'API_ERROR',
                                ],
                                'source' => [
                                    'type' => 'string',
                                    'example' => 'TMDB API',
                                ],
                                'timestamp' => [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                    'example' => '2025-12-04T12:00:00+00:00',
                                ],
                                'retry_after' => [
                                    'type' => 'integer',
                                    'example' => 30,
                                    'description' => 'Seconds to wait before retrying',
                                ],
                            ],
                        ],
                        'debug_info' => [
                            'type' => 'object',
                            'properties' => [
                                'request_id' => [
                                    'type' => 'string',
                                    'example' => 'req_123456789',
                                ],
                                'trace_id' => [
                                    'type' => 'string',
                                    'example' => 'trace_987654321',
                                ],
                            ],
                        ],
                    ],
                    'required' => ['error', 'message'],
                ],
            ],
        ]);
    }
}
