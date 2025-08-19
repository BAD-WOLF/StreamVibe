<?php

namespace App\ApiResource\Auth\ResetPassword;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ValidateResetTokenResponse;
use App\ApiResource\Auth\ResetPassword\Model\Request\RequestPasswordResetInput;
use App\ApiResource\Auth\ResetPassword\Model\Request\RequestPasswordResetOutput;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ResetPasswordInput;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ResetPasswordOutput;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/reset-password/request',
            status: 200,
            controller: \App\Controller\API\Auth\ResetPassword\ResetPasswordRequestController::class,
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
            input: RequestPasswordResetInput::class,
            output: RequestPasswordResetOutput::class,
            name: 'post_reset_password_request',
        ),
        new Get(
            uriTemplate: '/reset-password/validate/{token}',
            status: 200,
            controller: \App\Controller\API\Auth\ResetPassword\ResetPasswordResetController::class,
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
            output: ValidateResetTokenResponse::class,
            read: false,
            name: 'get_validate_reset_token',
        ),
        new Post(
            uriTemplate: '/reset-password/reset',
            status: 200,
            controller: \App\Controller\API\Auth\ResetPassword\ResetPasswordResetController::class,
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
            input: ResetPasswordInput::class,
            output: ResetPasswordOutput::class,
            name: 'post_reset_password',
        ),
    ],
)]
final class ResetPasswordEntryPoint
{
    // vazio: configura apenas rota, input e output
}