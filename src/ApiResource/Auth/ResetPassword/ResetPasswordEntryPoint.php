<?php

namespace App\ApiResource\Auth\ResetPassword;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\Auth\ResetPassword\ResetPasswordRequestController;
use App\Controller\Auth\ResetPassword\ResetPasswordResetController;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use App\ApiResource\Auth\ResetPassword\Model\Request;
use App\ApiResource\Auth\ResetPassword\Model\Reset;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ValidateResetTokenResponse;
use App\ApiResource\Auth\ResetPassword\Model\Request\RequestPasswordResetInput;
use App\ApiResource\Auth\ResetPassword\Model\Request\RequestPasswordResetOutput;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ResetPasswordInput;
use App\ApiResource\Auth\ResetPassword\Model\Reset\ResetPasswordOutput;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/reset-password/request',
            status: 200,
            controller: ResetPasswordRequestController::class,
            input: RequestPasswordResetInput::class,
            output: RequestPasswordResetOutput::class,
            name: 'post_reset_password_request'
        ),
        new Get(
            uriTemplate: '/reset-password/validate/{token}',
            status: 200,
            controller: ResetPasswordResetController::class,
            output: ValidateResetTokenResponse::class,
            read: false,
            name: 'get_validate_reset_token'
        ),
        new Post(
            uriTemplate: '/reset-password/reset',
            status: 200,
            controller: ResetPasswordResetController::class,
            input: ResetPasswordInput::class,
            output: ResetPasswordOutput::class,
            name: 'post_reset_password'
        ),
    ],
)]
final class ResetPasswordEntryPoint
{
    // vazio: configura apenas rota, input e output
}