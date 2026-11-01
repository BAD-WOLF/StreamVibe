<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Domain\User\Entity\User;

/**
 * Test builder for User entities using PHP 8.4 Property Hooks
 *
 * This builder demonstrates the use of property hooks for cleaner test code
 * and provides a fluent interface for creating User entities in tests.
 *
 * Features:
 * - Fluent interface for building User entities
 * - Property hooks for direct property access
 * - Virtual computed properties for validation
 * - Asymmetric visibility (public read, private write)
 * - Static factory methods for common scenarios
 * - Builder cloning and resetting capabilities
 *
 * @example
 * $user = UserTestBuilder::create()
 *     ->withEmail('test@example.com')
 *     ->withPassword('password')
 *     ->verified()
 *     ->build();
 */
final class UserTestBuilder
{
    public private(set) ?string $email = null {
        get => $this->email;
        set {
            if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email format: {$value}");
            }
            $this->email = $value;
        }
    }

    public private(set) ?string $password = null {
        get => $this->password;
        set {
            if ($value !== null && strlen($value) < 3) {
                throw new \InvalidArgumentException("Password too short: minimum 3 characters required");
            }
            $this->password = $value;
        }
    }

    public private(set) array $roles = [] {
        get => $this->roles;
        set(array $value) {
            // Validate that all roles are strings starting with ROLE_
            foreach ($value as $role) {
                if (!is_string($role) || !str_starts_with($role, 'ROLE_')) {
                    throw new \InvalidArgumentException("Invalid role format: {$role}. Roles must start with 'ROLE_'");
                }
            }
            $this->roles = array_unique($value);
        }
    }

    public private(set) bool $isVerified = false {
        get => $this->isVerified;
        set {
            $this->isVerified = $value;
        }
    }

    // Virtual computed properties using property hooks
    public bool $hasEmail {
        get => !empty($this->email);
    }

    public bool $hasPassword {
        get => !empty($this->password);
    }

    public bool $hasCustomRoles {
        get => !empty($this->roles);
    }

    public bool $isComplete {
        get => $this->hasEmail && $this->hasPassword;
    }

    public array $summary {
        get {
            return [
                'email' => $this->email,
                'has_password' => $this->hasPassword,
                'roles_count' => count($this->roles),
                'is_verified' => $this->isVerified,
                'is_complete' => $this->isComplete,
            ];
        }
    }

    /**
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function withEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function withPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function withRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function withRole(string $role): self
    {
        $currentRoles = $this->roles;
        $currentRoles[] = $role;
        $this->roles = $currentRoles;
        return $this;
    }

    /**
     * @param bool $isVerified
     *
     * @return $this
     */
    public function verified(bool $isVerified = true): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * @return $this
     */
    public function setUnverified(): self
    {
        return $this->verified(false);
    }

    /**
     * @return $this
     */
    public function asAdmin(): self
    {
        $currentRoles = $this->roles;
        $currentRoles[] = 'ROLE_ADMIN';
        $this->roles = $currentRoles;
        return $this;
    }

    /**
     * @return $this
     */
    public function asModerator(): self
    {
        $currentRoles = $this->roles;
        $currentRoles[] = 'ROLE_MODERATOR';
        $this->roles = $currentRoles;
        return $this;
    }

    /**
     * @return self
     */
    public function withDefaultTestData(): self
    {
        return $this
            ->withEmail('test@example.com')
            ->withPassword('hashed_password_123')
            ->verified();
    }

    /**
     * @return \App\Domain\User\Entity\User
     */
    public function build(): User
    {
        if (!$this->isComplete) {
            throw new \InvalidArgumentException(
                'UserTestBuilder requires email and password to build a User. ' .
                'Current state: ' . json_encode($this->summary)
            );
        }

        $user = new User();
        $user->setEmail($this->email);
        $user->setPassword($this->password);
        $user->setIsVerified($this->isVerified);

        if ($this->hasCustomRoles) {
            $user->setRoles($this->roles);
        }

        return $user;
    }

    /**
     * Build multiple users with incremented emails
     *
     * @param int $count Number of users to build
     * @param string $baseEmail Email template with {n} placeholder
     * @return array Array of User entities
     * @throws \InvalidArgumentException If password is missing or count is invalid
     */
    public function buildMany(int $count, string $baseEmail = 'user{n}@example.com'): array
    {
        if ($count <= 0) {
            throw new \InvalidArgumentException('Count must be greater than 0');
        }

        if ($count > 1000) {
            throw new \InvalidArgumentException('Count cannot exceed 1000 users for performance reasons');
        }

        if (!$this->hasPassword) {
            throw new \InvalidArgumentException('Password is required to build multiple users');
        }

        if (!str_contains($baseEmail, '{n}')) {
            throw new \InvalidArgumentException('Base email must contain {n} placeholder');
        }

        $users = [];

        for ($i = 1; $i <= $count; $i++) {
            $email = str_replace('{n}', (string)$i, $baseEmail);

            $builder = clone $this;
            $builder->email = $email;

            $users[] = $builder->build();
        }

        return $users;
    }

    /**
     * Create a random user for testing
     *
     * @return User Random user with unique email and password
     */
    public static function random(): User
    {
        $randomId = uniqid();

        return self::create()
            ->withEmail("test{$randomId}@example.com")
            ->withPassword("password_{$randomId}")
            ->verified(random_int(0, 1) === 1)
            ->build();
    }

    /**
     * Create a typical admin user
     *
     * @return User Admin user with ROLE_ADMIN privileges
     */
    public static function admin(): User
    {
        return self::create()
            ->withEmail('admin@example.com')
            ->withPassword('admin_password_hash')
            ->asAdmin()
            ->verified()
            ->build();
    }

    /**
     * Create a typical unverified user
     *
     * @return User Unverified user for email confirmation tests
     */
    public static function createUnverified(): User
    {
        return self::create()
            ->withEmail('unverified@example.com')
            ->withPassword('unverified_password_hash')
            ->setUnverified()
            ->build();
    }

    /**
     * Reset the builder to initial state
     *
     * @return self Fluent interface
     */
    public function reset(): self
    {
        $this->email = null;
        $this->password = null;
        $this->roles = [];
        $this->isVerified = false;

        return $this;
    }

    /**
     * Create a clone of the current builder
     *
     * @return self New builder instance with same data
     */
    public function clone(): self
    {
        $clone = new self();
        $clone->email = $this->email;
        $clone->password = $this->password;
        $clone->roles = $this->roles;
        $clone->isVerified = $this->isVerified;

        return $clone;
    }

    /**
     * Magic method called when object is cloned
     *
     * Ensures arrays are properly deep-cloned to avoid reference issues
     */
    public function __clone()
    {
        // Ensure arrays are properly cloned
        $this->roles = array_values($this->roles);
    }
}
