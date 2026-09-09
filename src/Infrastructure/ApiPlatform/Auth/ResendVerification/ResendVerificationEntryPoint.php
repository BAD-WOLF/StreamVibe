<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Auth\ResendVerification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Infrastructure\ApiPlatform\Auth\ResendVerification\Model\ResendVerificationInput;
use App\Infrastructure\ApiPlatform\Auth\ResendVerification\Model\ResendVerificationResponse;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\Http\Controller\Authentication\ResendVerificationController;

/**
 *
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/resend-verification',
            status: 200,
            controller: ResendVerificationController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: '_locale',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'default' => 'pt_BR',
                        ]
                    )),
                ]
            ),
            input: ResendVerificationInput::class,
            output: ResendVerificationResponse::class,
            name: 'post_resend_verification',
        ),
    ],
)]
final class ResendVerificationEntryPoint {
    // Empty class, because "ResendVerification" is just a logical grouping.
    // Its input and output are defined separately.
}
