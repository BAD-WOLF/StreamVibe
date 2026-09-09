<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema;

use ArrayObject;

/**
 *
 */
final class MovieApiErrorSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'An error occurred while processing the movie request'
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
                        'error_details' => [
                            'type' => 'object',
                            'properties' => [
                                'error_code' => [
                                    'type' => 'string',
                                    'example' => 'MOVIE_API_ERROR',
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
                                'request_id' => [
                                    'type' => 'string',
                                    'example' => 'req_123456789',
                                ],
                            ],
                        ],
                        'retry_info' => [
                            'type' => 'object',
                            'properties' => [
                                'retry_after' => [
                                    'type' => 'integer',
                                    'example' => 30,
                                    'description' => 'Seconds to wait before retrying',
                                ],
                                'max_retries' => [
                                    'type' => 'integer',
                                    'example' => 3,
                                ],
                                'exponential_backoff' => [
                                    'type' => 'boolean',
                                    'example' => true,
                                ],
                            ],
                        ],
                        'help' => [
                            'type' => 'object',
                            'properties' => [
                                'documentation' => [
                                    'type' => 'string',
                                    'example' => 'https://api.streamvibe.com/docs',
                                ],
                                'support' => [
                                    'type' => 'string',
                                    'example' => 'support@streamvibe.com',
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
