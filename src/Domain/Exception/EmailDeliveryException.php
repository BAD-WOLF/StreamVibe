<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class EmailDeliveryException extends DomainException
{
    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message,
        int $code = 503,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return 'email_delivery_error';
    }

    public static function deliveryFailed(string $recipient, string $reason = ''): self
    {
        $message = sprintf('Failed to deliver email to "%s"', $recipient);
        if ($reason) {
            $message .= sprintf(': %s', $reason);
        }

        return new self($message, 503, null, [
            'recipient' => $recipient,
            'reason' => $reason,
        ]);
    }

    public static function invalidEmailAddress(string $email): self
    {
        return new self(
            sprintf('Invalid email address: "%s"', $email),
            400,
            null,
            ['email' => $email]
        );
    }

    public static function templateNotFound(string $template): self
    {
        return new self(
            sprintf('Email template not found: "%s"', $template),
            404,
            null,
            ['template' => $template]
        );
    }

    public static function templateRenderingFailed(string $template, string $error): self
    {
        return new self(
            sprintf('Failed to render email template "%s": %s', $template, $error),
            500,
            null,
            [
                'template' => $template,
                'error' => $error,
            ]
        );
    }

    public static function smtpConnectionFailed(string $host, int $port = 587): self
    {
        return new self(
            sprintf('Failed to connect to SMTP server at %s:%d', $host, $port),
            503,
            null,
            [
                'smtp_host' => $host,
                'smtp_port' => $port,
            ]
        );
    }

    public static function smtpAuthenticationFailed(string $username): self
    {
        return new self(
            sprintf('SMTP authentication failed for user "%s"', $username),
            401,
            null,
            ['username' => $username]
        );
    }

    public static function attachmentNotFound(string $filePath): self
    {
        return new self(
            sprintf('Email attachment not found: "%s"', $filePath),
            404,
            null,
            ['file_path' => $filePath]
        );
    }

    public static function attachmentTooLarge(string $fileName, int $size, int $maxSize): self
    {
        return new self(
            sprintf(
                'Email attachment "%s" is too large (%d bytes). Maximum allowed: %d bytes',
                $fileName,
                $size,
                $maxSize
            ),
            413,
            null,
            [
                'file_name' => $fileName,
                'file_size' => $size,
                'max_size' => $maxSize,
            ]
        );
    }

    public static function quotaExceeded(string $recipient, int $dailyLimit): self
    {
        return new self(
            sprintf(
                'Daily email quota exceeded for recipient "%s". Limit: %d emails per day',
                $recipient,
                $dailyLimit
            ),
            429,
            null,
            [
                'recipient' => $recipient,
                'daily_limit' => $dailyLimit,
            ]
        );
    }

    public static function bounceReceived(string $recipient, string $bounceReason): self
    {
        return new self(
            sprintf('Email bounced for recipient "%s": %s', $recipient, $bounceReason),
            422,
            null,
            [
                'recipient' => $recipient,
                'bounce_reason' => $bounceReason,
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
