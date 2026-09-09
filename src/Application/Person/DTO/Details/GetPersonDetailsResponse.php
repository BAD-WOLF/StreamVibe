<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Details;

use Exception;
use DateTime;
use Symfony\Component\Serializer\Attribute\Ignore;
/**
 * NOTA (ago/2026): movieCredits/tvCredits/images/externalIds e todos os
 * métodos/propriedades computadas relacionados foram removidos. Movie
 * credits ganhou endpoint dedicado. Os outros três nunca tiveram
 * implementação real.
 */
final class GetPersonDetailsResponse {
    public function __construct(
        #[Ignore]
        public private(set) bool $success {
            get => $this->success;
        },
        public private(set) ?array $personDetails = null {
            get => $this->personDetails;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            get => $this->message;
        },
        #[Ignore]
        public private(set) array $errors = [] {
            get => $this->errors;
        },
    ) {
    }

    public static function success(
        array $personDetails,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            personDetails: $personDetails,
            message: $message,
        );
    }

    public static function failure(string $message, array $errors = []): self {
        return new self(success: false, message: $message, errors: $errors);
    }

    // Virtual computed properties — todos internos, nunca expostos na API
    #[Ignore]
    public bool $isSuccess {
        get => $this->success;
    }

    #[Ignore]
    public bool $hasErrors {
        get => !empty($this->errors);
    }

    // Person detail virtual properties — atalhos pra dentro de $personDetails,
    // que já vem exposto por inteiro; sem #[Ignore] apareceriam duplicados
    #[Ignore]
    public ?int $personId {
        get => $this->personDetails['id'] ?? null;
    }

    #[Ignore]
    public ?string $personName {
        get => $this->personDetails['name'] ?? null;
    }

    #[Ignore]
    public ?string $personBiography {
        get => $this->personDetails['biography'] ?? null;
    }

    #[Ignore]
    public ?string $personBirthday {
        get => $this->personDetails['birthday'] ?? null;
    }

    #[Ignore]
    public ?string $personDeathday {
        get => $this->personDetails['deathday'] ?? null;
    }

    #[Ignore]
    public ?string $personPlaceOfBirth {
        get => $this->personDetails['place_of_birth'] ?? null;
    }

    #[Ignore]
    public ?string $personProfilePath {
        get => $this->personDetails['profile_path'] ?? null;
    }

    #[Ignore]
    public ?string $personKnownForDepartment {
        get => $this->personDetails['known_for_department'] ?? null;
    }

    #[Ignore]
    public ?float $personPopularity {
        get => $this->personDetails['popularity'] ?? null;
    }

    #[Ignore]
    public ?int $personGender {
        get => $this->personDetails['gender'] ?? null;
    }

    #[Ignore]
    public array $personAlsoKnownAs {
        get => $this->personDetails['also_known_as'] ?? [];
    }

    #[Ignore]
    public bool $isPersonAlive {
        get => empty($this->personDeathday);
    }

    #[Ignore]
    public ?int $personAge {
        get {
            $birthday = $this->personBirthday;
            if (!$birthday) {
                return null;
            }

            try {
                $birthDate = new DateTime($birthday);
                $endDate = $this->personDeathday
                    ? new DateTime($this->personDeathday)
                    : new DateTime();

                return $birthDate->diff($endDate)->y;
            } catch (Exception) {
                return null;
            }
        }
    }

    #[Ignore]
    public array $asArray {
        get {
            $data = [
                'success' => $this->success,
                'message' => $this->message,
                'errors' => $this->errors,
            ];

            if ($this->success && $this->personDetails !== null) {
                $data['data'] = [
                    'person' => $this->personDetails,
                ];
            }

            return $data;
        }
    }

    // Compatibility methods
    #[Ignore]
    public function isSuccess(): bool {
        return $this->isSuccess;
    }

    public function getPersonDetails(): ?array {
        return $this->personDetails;
    }

    public function getMessage(): ?string {
        return $this->message;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    #[Ignore]
    public function hasErrors(): bool {
        return $this->hasErrors;
    }

    public function getPersonId(): ?int {
        return $this->personId;
    }

    public function getPersonName(): ?string {
        return $this->personName;
    }

    public function getPersonBiography(): ?string {
        return $this->personBiography;
    }

    public function getPersonBirthday(): ?string {
        return $this->personBirthday;
    }

    public function getPersonDeathday(): ?string {
        return $this->personDeathday;
    }

    public function getPersonPlaceOfBirth(): ?string {
        return $this->personPlaceOfBirth;
    }

    public function getPersonProfilePath(): ?string {
        return $this->personProfilePath;
    }

    public function getPersonKnownForDepartment(): ?string {
        return $this->personKnownForDepartment;
    }

    public function getPersonPopularity(): ?float {
        return $this->personPopularity;
    }

    public function getPersonGender(): ?int {
        return $this->personGender;
    }

    public function getPersonAlsoKnownAs(): array {
        return $this->personAlsoKnownAs;
    }

    public function isPersonAlive(): bool {
        return $this->isPersonAlive;
    }

    public function getPersonAge(): ?int {
        return $this->personAge;
    }

    public function toArray(): array {
        return $this->asArray;
    }
}
