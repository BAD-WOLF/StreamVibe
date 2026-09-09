<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class ResetPasswordValidationException extends ValidationException
{
    public function __construct(
        string $message = "Reset password validation failed",
        array $errors = [],
        int $code = 400,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $errors, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return "reset_password_validation_error";
    }

    public static function passwordTooShort(int $minLength): self
    {
        return new self(
            "Password is too short",
            [sprintf("Password must be at least %d characters long", $minLength)],
            400,
            null,
            ["min_length" => $minLength]
        );
    }

    public static function passwordTooWeak(): self
    {
        return new self(
            "Password is too weak",
            [
                "Password must contain at least one uppercase letter",
                "Password must contain at least one lowercase letter",
                "Password must contain at least one number",
                "Password must contain at least one special character"
            ],
            400,
            null,
            ["requirements" => ["uppercase", "lowercase", "number", "special"]]
        );
    }

    public static function passwordsDoNotMatch(): self
    {
        return new self(
            "Password confirmation does not match",
            ["The password and password confirmation must match"],
            400,
            null,
            ["field" => "password_confirmation"]
        );
    }

    public static function invalidNewPassword(array $violations): self
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $violation;
        }

        return new self(
            "New password validation failed",
            $errors,
            400,
            null,
            ["violations_count" => count($violations)]
        );
    }

    public static function passwordReusedRecently(int $historyCount): self
    {
        return new self(
            "Password was used recently",
            [sprintf("Cannot reuse any of the last %d passwords", $historyCount)],
            400,
            null,
            ["history_count" => $historyCount]
        );
    }

    public static function passwordSameAsCurrent(): self
    {
        return new self(
            "New password cannot be the same as current password",
            ["The new password must be different from the current password"],
            400,
            null,
            ["field" => "new_password"]
        );
    }

    public static function tokenRequired(): self
    {
        return new self(
            "Reset token is required",
            ["A valid password reset token is required"],
            400,
            null,
            ["field" => "token"]
        );
    }

    public static function emailRequired(): self
    {
        return new self(
            "Email is required",
            ["Email address is required for password reset"],
            400,
            null,
            ["field" => "email"]
        );
    }

    public static function invalidEmailFormat(string $email): self
    {
        return new self(
            "Invalid email format",
            ["The provided email address format is invalid"],
            400,
            null,
            [
                "field" => "email",
                "value" => $email
            ]
        );
    }

    public static function passwordRequired(): self
    {
        return new self(
            "Password is required",
            ["New password is required"],
            400,
            null,
            ["field" => "password"]
        );
    }

    public static function confirmationRequired(): self
    {
        return new self(
            "Password confirmation is required",
            ["Password confirmation is required"],
            400,
            null,
            ["field" => "password_confirmation"]
        );
    }

    public static function invalidPasswordComplexity(array $missingRequirements): self
    {
        $errors = [];
        foreach ($missingRequirements as $requirement) {
            switch ($requirement) {
                case 'length':
                    $errors[] = "Password must be at least 8 characters long";
                    break;
                case 'uppercase':
                    $errors[] = "Password must contain at least one uppercase letter";
                    break;
                case 'lowercase':
                    $errors[] = "Password must contain at least one lowercase letter";
                    break;
                case 'number':
                    $errors[] = "Password must contain at least one number";
                    break;
                case 'special':
                    $errors[] = "Password must contain at least one special character";
                    break;
                case 'no_spaces':
                    $errors[] = "Password cannot contain spaces";
                    break;
                default:
                    $errors[] = sprintf("Password requirement not met: %s", $requirement);
            }
        }

        return new self(
            "Password does not meet complexity requirements",
            $errors,
            400,
            null,
            ["missing_requirements" => $missingRequirements]
        );
    }

    public static function commonPasswordDetected(): self
    {
        return new self(
            "Password is too common",
            ["The password you chose is too common. Please choose a more unique password"],
            400,
            null,
            ["security_issue" => "common_password"]
        );
    }

    public static function containsPersonalInfo(): self
    {
        return new self(
            "Password contains personal information",
            ["Password cannot contain personal information like name, email, or username"],
            400,
            null,
            ["security_issue" => "personal_info"]
        );
    }

    public function toArray(): array
    {
        return [
            "type" => $this->getErrorType(),
            "message" => $this->getMessage(),
            "errors" => $this->errors,
            "context" => $this->context,
        ];
    }
}
