<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class UserValidationException extends ValidationException
{
    public function __construct(
        string $message = "User validation failed",
        array $errors = [],
        int $code = 400,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $errors, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return "user_validation_error";
    }

    public static function invalidEmail(string $email): self
    {
        return new self(
            "Invalid email address",
            ["The email address format is invalid"],
            400,
            null,
            ["email" => $email]
        );
    }

    public static function emailTooLong(string $email, int $maxLength): self
    {
        return new self(
            "Email address is too long",
            [sprintf("Email address must not exceed %d characters", $maxLength)],
            400,
            null,
            [
                "email" => $email,
                "max_length" => $maxLength,
                "actual_length" => strlen($email)
            ]
        );
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

    public static function passwordTooLong(int $maxLength): self
    {
        return new self(
            "Password is too long",
            [sprintf("Password must not exceed %d characters", $maxLength)],
            400,
            null,
            ["max_length" => $maxLength]
        );
    }

    public static function weakPassword(): self
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

    public static function usernameTooShort(int $minLength): self
    {
        return new self(
            "Username is too short",
            [sprintf("Username must be at least %d characters long", $minLength)],
            400,
            null,
            ["min_length" => $minLength]
        );
    }

    public static function usernameTooLong(string $username, int $maxLength): self
    {
        return new self(
            "Username is too long",
            [sprintf("Username must not exceed %d characters", $maxLength)],
            400,
            null,
            [
                "username" => $username,
                "max_length" => $maxLength,
                "actual_length" => strlen($username)
            ]
        );
    }

    public static function invalidUsername(string $username): self
    {
        return new self(
            "Invalid username format",
            ["Username can only contain letters, numbers, underscores, and hyphens"],
            400,
            null,
            ["username" => $username]
        );
    }

    public static function reservedUsername(string $username): self
    {
        return new self(
            "Username is reserved",
            [sprintf('Username "%s" is reserved and cannot be used', $username)],
            400,
            null,
            ["username" => $username]
        );
    }

    public static function firstNameRequired(): self
    {
        return new self(
            "First name is required",
            ["First name cannot be empty"],
            400,
            null,
            ["field" => "first_name"]
        );
    }

    public static function firstNameTooLong(string $firstName, int $maxLength): self
    {
        return new self(
            "First name is too long",
            [sprintf("First name must not exceed %d characters", $maxLength)],
            400,
            null,
            [
                "first_name" => $firstName,
                "max_length" => $maxLength,
                "actual_length" => strlen($firstName)
            ]
        );
    }

    public static function lastNameRequired(): self
    {
        return new self(
            "Last name is required",
            ["Last name cannot be empty"],
            400,
            null,
            ["field" => "last_name"]
        );
    }

    public static function lastNameTooLong(string $lastName, int $maxLength): self
    {
        return new self(
            "Last name is too long",
            [sprintf("Last name must not exceed %d characters", $maxLength)],
            400,
            null,
            [
                "last_name" => $lastName,
                "max_length" => $maxLength,
                "actual_length" => strlen($lastName)
            ]
        );
    }

    public static function invalidPhoneNumber(string $phoneNumber): self
    {
        return new self(
            "Invalid phone number format",
            ["Phone number format is invalid"],
            400,
            null,
            ["phone_number" => $phoneNumber]
        );
    }

    public static function invalidDateOfBirth(string $dateOfBirth): self
    {
        return new self(
            "Invalid date of birth",
            ["Date of birth must be in valid format (YYYY-MM-DD)"],
            400,
            null,
            ["date_of_birth" => $dateOfBirth]
        );
    }

    public static function userTooYoung(int $age, int $minimumAge): self
    {
        return new self(
            "User is too young",
            [sprintf("User must be at least %d years old", $minimumAge)],
            400,
            null,
            [
                "age" => $age,
                "minimum_age" => $minimumAge
            ]
        );
    }

    public static function invalidGender(string $gender, array $allowedGenders): self
    {
        return new self(
            "Invalid gender",
            [sprintf("Gender must be one of: %s", implode(", ", $allowedGenders))],
            400,
            null,
            [
                "gender" => $gender,
                "allowed_genders" => $allowedGenders
            ]
        );
    }

    public static function invalidCountryCode(string $countryCode): self
    {
        return new self(
            "Invalid country code",
            ["Country code must be a valid ISO 3166-1 alpha-2 code"],
            400,
            null,
            ["country_code" => $countryCode]
        );
    }

    public static function invalidLanguageCode(string $languageCode): self
    {
        return new self(
            "Invalid language code",
            ["Language code must be a valid ISO 639-1 code"],
            400,
            null,
            ["language_code" => $languageCode]
        );
    }

    public static function invalidTimezone(string $timezone): self
    {
        return new self(
            "Invalid timezone",
            ["Timezone must be a valid timezone identifier"],
            400,
            null,
            ["timezone" => $timezone]
        );
    }

    public static function profilePictureTooLarge(int $fileSize, int $maxSize): self
    {
        return new self(
            "Profile picture is too large",
            [sprintf("Profile picture must not exceed %d bytes", $maxSize)],
            400,
            null,
            [
                "file_size" => $fileSize,
                "max_size" => $maxSize,
                "formatted_size" => self::formatBytes($fileSize),
                "max_formatted_size" => self::formatBytes($maxSize)
            ]
        );
    }

    public static function invalidProfilePictureFormat(string $mimeType, array $allowedTypes): self
    {
        return new self(
            "Invalid profile picture format",
            [sprintf("Profile picture must be one of: %s", implode(", ", $allowedTypes))],
            400,
            null,
            [
                "mime_type" => $mimeType,
                "allowed_types" => $allowedTypes
            ]
        );
    }

    public static function bioTooLong(string $bio, int $maxLength): self
    {
        return new self(
            "Bio is too long",
            [sprintf("Bio must not exceed %d characters", $maxLength)],
            400,
            null,
            [
                "bio" => substr($bio, 0, 50) . "...",
                "max_length" => $maxLength,
                "actual_length" => strlen($bio)
            ]
        );
    }

    public static function multipleValidationErrors(array $fieldErrors): self
    {
        $errors = [];
        $context = ["field_errors" => []];

        foreach ($fieldErrors as $field => $fieldError) {
            $errors[] = sprintf("%s: %s", ucfirst($field), $fieldError);
            $context["field_errors"][$field] = $fieldError;
        }

        return new self(
            "Multiple validation errors occurred",
            $errors,
            400,
            null,
            $context
        );
    }

    public static function requiredFieldMissing(string $fieldName): self
    {
        return new self(
            sprintf("Required field '%s' is missing", $fieldName),
            [sprintf("The field '%s' is required", $fieldName)],
            400,
            null,
            ["field" => $fieldName]
        );
    }

    public static function invalidFieldValue(string $fieldName, mixed $value, string $expectedType): self
    {
        return new self(
            sprintf("Invalid value for field '%s'", $fieldName),
            [sprintf("Field '%s' expects %s, got %s", $fieldName, $expectedType, gettype($value))],
            400,
            null,
            [
                "field" => $fieldName,
                "value" => is_scalar($value) ? $value : gettype($value),
                "expected_type" => $expectedType
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
            "type" => $this->getErrorType(),
            "message" => $this->getMessage(),
            "errors" => $this->errors,
            "context" => $this->context,
        ];
    }
}
