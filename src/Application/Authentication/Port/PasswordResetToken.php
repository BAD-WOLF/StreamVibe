<?php

declare(strict_types = 1);

namespace App\Application\Authentication\Port;

use DateTimeImmutable;

/**
 * Um token de troca de senha recém-gerado (PasswordResetInterface).
 *
 * expirationMessageKey/expirationMessageData são o "expira em 1 hora"
 * do email, no formato de tradução do ResetPasswordBundle; o template
 * reset_password/email.html.twig os lê como resetToken.token,
 * resetToken.expirationMessageKey e resetToken.expirationMessageData —
 * os mesmos nomes do ResetPasswordToken do bundle, que era o objeto
 * passado antes.
 *
 * NOTA (out/2026): criado junto com a PasswordResetInterface, para o
 * Application não depender do ResetPasswordToken do bundle.
 */
final readonly class PasswordResetToken {
    /**
     * @param string               $token                o token do link (40 caracteres)
     * @param \DateTimeImmutable   $expiresAt
     * @param string               $expirationMessageKey ex.: "%count% hour|%count% hours"
     * @param array<string, mixed> $expirationMessageData ex.: ["%count%" => 1]
     */
    public function __construct(
        public string $token,
        public DateTimeImmutable $expiresAt,
        public string $expirationMessageKey,
        public array $expirationMessageData,
    ) {
    }
}
