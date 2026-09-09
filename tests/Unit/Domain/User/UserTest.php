<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Entity\User;
use App\Tests\Helper\UserTestBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Unit tests for User entity
 *
 * Tests the User domain entity behavior including:
 * - Entity creation and default values
 * - Property setters and getters
 * - Role management
 * - Verification status
 * - Interface compliance
 * - Fluent interface behavior
 */
final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserCreationHasCorrectDefaults(): void
    {
        // Assert new user has expected default values
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getEmail());
        $this->assertEquals(["ROLE_USER"], $this->user->getRoles());
        $this->assertNull($this->user->getPassword());
        $this->assertFalse($this->user->isVerified());
    }

    public function testSetEmailReturnsFluentInterface(): void
    {
        // Arrange
        $email = "test@example.com";

        // Act
        $result = $this->user->setEmail($email);

        // Assert
        $this->assertSame($this->user, $result);
        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        // Arrange
        $email = "user@example.com";
        $this->user->setEmail($email);

        // Act & Assert
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testGetUserIdentifierWithNullEmailReturnsEmptyString(): void
    {
        // Act & Assert
        $this->assertEquals("", $this->user->getUserIdentifier());
    }

    public function testSetPasswordReturnsFluentInterface(): void
    {
        // Arrange
        $password = "hashed_password_123";

        // Act
        $result = $this->user->setPassword($password);

        // Assert
        $this->assertSame($this->user, $result);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testGetRolesAlwaysIncludesDefaultRole(): void
    {
        // Act
        $roles = $this->user->getRoles();

        // Assert
        $this->assertContains("ROLE_USER", $roles);
        $this->assertEquals(["ROLE_USER"], $roles);
    }

    public function testSetRolesAddsDefaultRoleAutomatically(): void
    {
        // Arrange
        $customRoles = ["ROLE_ADMIN", "ROLE_MODERATOR"];

        // Act
        $result = $this->user->setRoles($customRoles);

        // Assert
        $this->assertSame($this->user, $result);

        $roles = $this->user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertContains("ROLE_MODERATOR", $roles);
        $this->assertCount(3, $roles);
    }

    public function testSetRolesRemovesDuplicateRoles(): void
    {
        // Arrange - Include ROLE_USER which is added automatically
        $rolesWithDuplicates = [
            "ROLE_USER",
            "ROLE_ADMIN",
            "ROLE_USER",
            "ROLE_ADMIN",
        ];

        // Act
        $this->user->setRoles($rolesWithDuplicates);

        // Assert
        $roles = $this->user->getRoles();
        $this->assertCount(2, $roles);
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
    }

    public function testSetIsVerifiedReturnsFluentInterface(): void
    {
        // Act
        $result = $this->user->setIsVerified(true);

        // Assert
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->isVerified());
    }

    public function testVerificationStatusCanBeToggled(): void
    {
        // Assert initial state
        $this->assertFalse($this->user->isVerified());

        // Act - Set to verified
        $this->user->setIsVerified(true);

        // Assert verified state
        $this->assertTrue($this->user->isVerified());

        // Act - Set back to unverified
        $this->user->setIsVerified(false);

        // Assert unverified state
        $this->assertFalse($this->user->isVerified());
    }

    public function testEraseCredentialsDoesNotThrowException(): void
    {
        // Act & Assert - Method should be callable without issues
        $this->user->eraseCredentials();
        $this->expectNotToPerformAssertions();
    }

    public function testUserImplementsRequiredInterfaces(): void
    {
        // Assert implements Symfony Security interfaces
        $this->assertInstanceOf(UserInterface::class, $this->user);
        $this->assertInstanceOf(
            PasswordAuthenticatedUserInterface::class,
            $this->user,
        );
    }

    public function testCompleteUserSetupWithFluentInterface(): void
    {
        // Arrange
        $email = "complete@example.com";
        $password = "hashed_password_456";
        $roles = ["ROLE_ADMIN"];

        // Act - Use fluent interface
        $result = $this->user
            ->setEmail($email)
            ->setPassword($password)
            ->setRoles($roles)
            ->setIsVerified(true);

        // Assert fluent interface works
        $this->assertSame($this->user, $result);

        // Assert all properties set correctly
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($password, $this->user->getPassword());
        $this->assertTrue($this->user->isVerified());

        $roles = $this->user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertCount(2, $roles);

        // Assert user identifier matches email
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testUserCanBeBuiltWithTestBuilder(): void
    {
        // Act
        $user = UserTestBuilder::create()
                               ->withEmail("builder@example.com")
                               ->withPassword("password123")
                               ->withRole("ROLE_MODERATOR")
                               ->verified()
                               ->build();

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals("builder@example.com", $user->getEmail());
        $this->assertEquals("password123", $user->getPassword());
        $this->assertTrue($user->isVerified());

        $roles = $user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_MODERATOR", $roles);
    }

    public function testUserRoleManagementEdgeCases(): void
    {
        // Test empty roles array
        $this->user->setRoles([]);
        $roles = $this->user->getRoles();
        $this->assertEquals(["ROLE_USER"], $roles);

        // Test null handling would be done in setter validation
        $this->user->setRoles(["ROLE_ADMIN", "ROLE_SUPER_ADMIN"]);
        $roles = $this->user->getRoles();
        $this->assertCount(3, $roles); // ROLE_USER + 2 custom roles
        $this->assertContains("ROLE_USER", $roles);
        $this->assertContains("ROLE_ADMIN", $roles);
        $this->assertContains("ROLE_SUPER_ADMIN", $roles);
    }

    /**
     * NOTA: removida a parte que testava setEmail(null). A assinatura atual
     * de setEmail() é "string $email" (não nullable) — passar null é
     * TypeError, não um caso de negócio válido. O estado "email nulo" da
     * entidade recém-criada já está coberto em
     * testUserCreationHasCorrectDefaults().
     */
    public function testUserEmailHandling(): void
    {
        $validEmail = "valid@example.com";
        $this->user->setEmail($validEmail);
        $this->assertEquals($validEmail, $this->user->getEmail());
        $this->assertEquals($validEmail, $this->user->getUserIdentifier());
    }

    /**
     * NOTA: mesma razão do teste acima — setPassword() é "string $password"
     * (não nullable).
     */
    public function testUserPasswordHandling(): void
    {
        $password = "secure_hashed_password";
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testUserEntityConsistency(): void
    {
        // Arrange - Create user with all properties
        $email = "consistency@test.com";
        $password = "test_password_hash";
        $roles = ["ROLE_ADMIN", "ROLE_MODERATOR"];

        // Act
        $this->user
            ->setEmail($email)
            ->setPassword($password)
            ->setRoles($roles)
            ->setIsVerified(true);

        // Assert - Multiple calls return consistent results
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($email, $this->user->getEmail()); // Second call
        $this->assertEquals($email, $this->user->getUserIdentifier());

        $this->assertEquals($password, $this->user->getPassword());
        $this->assertEquals($password, $this->user->getPassword()); // Second call

        $this->assertTrue($this->user->isVerified());
        $this->assertTrue($this->user->isVerified()); // Second call

        $firstRolesCall = $this->user->getRoles();
        $secondRolesCall = $this->user->getRoles();
        $this->assertEquals($firstRolesCall, $secondRolesCall);
    }

    public function testUserMethodsPerformance(): void
    {
        // Arrange
        $this->user
            ->setEmail("performance@test.com")
            ->setPassword("password")
            ->setRoles(["ROLE_ADMIN"])
            ->setIsVerified(true);

        // Act - Measure performance of multiple method calls
        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $this->user->getEmail();
            $this->user->getPassword();
            $this->user->getRoles();
            $this->user->isVerified();
            $this->user->getUserIdentifier();
        }

        $end = microtime(true);
        $duration = $end - $start;

        // Assert - Should complete quickly
        $this->assertLessThan(
            0.1,
            $duration,
            "User entity methods should have good performance (completed in {$duration}s)",
        );
    }
}