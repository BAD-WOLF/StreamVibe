<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class RateLimitExceededException extends DomainException
{
    public function __construct(
        string $message,
        int $code = 429,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'rate_limit_exceeded_error';
    }

    public static function tooManyRequests(int $limit, int $windowInSeconds, ?int $retryAfter = null): self
    {
        $message = sprintf(
            'Rate limit exceeded. Maximum %d requests allowed per %d seconds',
            $limit,
            $windowInSeconds
        );

        $context = [
            'limit' => $limit,
            'window_seconds' => $windowInSeconds,
        ];

        if ($retryAfter !== null) {
            $message .= sprintf('. Try again in %d seconds', $retryAfter);
            $context['retry_after'] = $retryAfter;
        }

        return new self($message, 429, null, $context);
    }

    public static function dailyLimitExceeded(int $dailyLimit, ?int $resetAtHour = null): self
    {
        $message = sprintf('Daily rate limit of %d requests exceeded', $dailyLimit);
        $context = ['daily_limit' => $dailyLimit];

        if ($resetAtHour !== null) {
            $message .= sprintf('. Limit resets at %02d:00', $resetAtHour);
            $context['reset_hour'] = $resetAtHour;
        }

        return new self($message, 429, null, $context);
    }

    public static function hourlyLimitExceeded(int $hourlyLimit): self
    {
        return new self(
            sprintf('Hourly rate limit of %d requests exceeded', $hourlyLimit),
            429,
            null,
            ['hourly_limit' => $hourlyLimit]
        );
    }

    public static function minuteLimitExceeded(int $minuteLimit): self
    {
        return new self(
            sprintf('Per-minute rate limit of %d requests exceeded', $minuteLimit),
            429,
            null,
            ['minute_limit' => $minuteLimit]
        );
    }

    public static function concurrentRequestsExceeded(int $maxConcurrent): self
    {
        return new self(
            sprintf('Maximum concurrent requests exceeded. Limit: %d', $maxConcurrent),
            429,
            null,
            ['max_concurrent' => $maxConcurrent]
        );
    }

    public static function userLimitExceeded(int $userId, int $limit, string $period = 'hour'): self
    {
        return new self(
            sprintf(
                'User %d has exceeded the rate limit of %d requests per %s',
                $userId,
                $limit,
                $period
            ),
            429,
            null,
            [
                'user_id' => $userId,
                'limit' => $limit,
                'period' => $period,
            ]
        );
    }

    public static function apiKeyLimitExceeded(string $apiKey, int $limit, string $period = 'hour'): self
    {
        return new self(
            sprintf(
                'API key has exceeded the rate limit of %d requests per %s',
                $limit,
                $period
            ),
            429,
            null,
            [
                'api_key' => substr($apiKey, 0, 8) . '***',
                'limit' => $limit,
                'period' => $period,
            ]
        );
    }

    public static function ipLimitExceeded(string $ipAddress, int $limit, string $period = 'hour'): self
    {
        return new self(
            sprintf(
                'IP address %s has exceeded the rate limit of %d requests per %s',
                $ipAddress,
                $limit,
                $period
            ),
            429,
            null,
            [
                'ip_address' => $ipAddress,
                'limit' => $limit,
                'period' => $period,
            ]
        );
    }

    public static function endpointLimitExceeded(string $endpoint, int $limit, string $period = 'hour'): self
    {
        return new self(
            sprintf(
                'Endpoint "%s" has exceeded the rate limit of %d requests per %s',
                $endpoint,
                $limit,
                $period
            ),
            429,
            null,
            [
                'endpoint' => $endpoint,
                'limit' => $limit,
                'period' => $period,
            ]
        );
    }

    public static function downloadLimitExceeded(int $bytesLimit, string $period = 'day'): self
    {
        return new self(
            sprintf(
                'Download limit of %d bytes per %s exceeded',
                $bytesLimit,
                $period
            ),
            429,
            null,
            [
                'bytes_limit' => $bytesLimit,
                'period' => $period,
                'formatted_limit' => self::formatBytes($bytesLimit),
            ]
        );
    }

    public static function uploadLimitExceeded(int $filesLimit, string $period = 'hour'): self
    {
        return new self(
            sprintf(
                'Upload limit of %d files per %s exceeded',
                $filesLimit,
                $period
            ),
            429,
            null,
            [
                'files_limit' => $filesLimit,
                'period' => $period,
            ]
        );
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
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
