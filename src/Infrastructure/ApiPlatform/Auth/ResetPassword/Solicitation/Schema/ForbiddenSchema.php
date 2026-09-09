<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\Schema;

use ArrayObject;

/**
 *
 */
final class ForbiddenSchema extends ArrayObject {
    /**
     * @param string $exampleMessage
     */
    public function __construct(
        private readonly string $exampleMessage = 'User account not verified'
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
                        'reason' => [
                            'type' => 'string',
                            'example' => 'ACCOUNT_NOT_VERIFIED',
                        ],
                        'required_action' => [
                            'type' => 'string',
                            'example' => 'Please verify your email address before requesting password reset',
                        ],
                        'verification_options' => [
                            'type' => 'object',
                            'properties' => [
                                'resend_verification' => [
                                    'type' => 'string',
                                    'example' => '/api/resend-verification',
                                ],
                                'contact_support' => [
                                    'type' => 'string',
                                    'example' => 'support@streamvibe.com',
                                ],
                            ],
                        ],
                    ],
                    'required' => ['error', 'message', 'reason'],
                ],
            ],
        ]);
    }
}
